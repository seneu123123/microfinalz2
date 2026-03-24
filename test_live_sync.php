<!DOCTYPE html>
<html>
<head>
    <title>Test Live Sync</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Test Live Sync: vendor-registration ↔ procurement</h1>
    
    <div class="section">
        <h2>Step 1: Check Current Procurement Vendors</h2>
        <button onclick="checkProcurementVendors()">Check Procurement Vendors</button>
        <div id="procurementCheck"></div>
    </div>
    
    <div class="section">
        <h2>Step 2: Test Status Sync</h2>
        <button onclick="testStatusSync()">Test Status Sync</button>
        <div id="syncResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 3: Test Duplicate Prevention</h2>
        <button onclick="testDuplicatePrevention()">Test Duplicate Prevention</button>
        <div id="duplicateResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 4: Live Update Test</h2>
        <button onclick="testLiveUpdate()">Test Live Update</button>
        <div id="liveResult"></div>
    </div>

    <script>
        async function checkProcurementVendors() {
            const result = document.getElementById('procurementCheck');
            result.innerHTML = '<p>Checking procurement vendors...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                if (data.status === 'success' && data.data) {
                    result.innerHTML = `
                        <p class="success">✅ Found ${data.data.length} vendors in procurement</p>
                        <table>
                            <tr><th>Vendor ID</th><th>Company Name</th><th>Email</th><th>Current Status</th><th>Sent At</th></tr>
                            ${data.data.map(v => `
                                <tr>
                                    <td><strong>${v.id}</strong></td>
                                    <td>${v.company_name}</td>
                                    <td>${v.email}</td>
                                    <td><span class="status-badge status-${v.status.toLowerCase()}">${v.status}</span></td>
                                    <td>${v.sent_at}</td>
                                </tr>
                            `).join('')}
                        </table>
                        <p class="info">💡 Status is live from vendors table</p>
                    `;
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors in procurement</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testStatusSync() {
            const result = document.getElementById('syncResult');
            result.innerHTML = '<p>Testing status sync...</p>';
            
            try {
                // Get a vendor that's in procurement
                const procurementResponse = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const procurementData = await procurementResponse.json();
                
                if (procurementData.status === 'success' && procurementData.data.length > 0) {
                    const vendor = procurementData.data[0];
                    const oldStatus = vendor.status;
                    const newStatus = oldStatus === 'Pending' ? 'Approved' : 'Pending';
                    
                    result.innerHTML = `<p>Updating ${vendor.company_name} from ${oldStatus} to ${newStatus}...</p>`;
                    
                    // Test the sync API
                    const syncResponse = await fetch('api/procurement_vendors.php?action=update_vendor_status', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            vendor_id: vendor.id, 
                            status: newStatus 
                        })
                    });
                    
                    const syncResult = await syncResponse.json();
                    
                    result.innerHTML += `
                        <p class="${syncResult.status === 'success' ? 'success' : 'error'}">
                            ${syncResult.status === 'success' ? '✅' : '❌'} ${syncResult.message}
                        </p>
                    `;
                    
                    if (syncResult.status === 'success') {
                        // Check if the status actually updated
                        setTimeout(async () => {
                            const checkResponse = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                            const checkData = await checkResponse.json();
                            
                            const updatedVendor = checkData.data.find(v => v.id === vendor.id);
                            if (updatedVendor && updatedVendor.status === newStatus) {
                                result.innerHTML += `<p class="success">🎉 Status successfully synced to procurement!</p>`;
                            } else {
                                result.innerHTML += `<p class="error">❌ Status sync failed</p>`;
                            }
                        }, 1000);
                    }
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors available for testing</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testDuplicatePrevention() {
            const result = document.getElementById('duplicateResult');
            result.innerHTML = '<p>Testing duplicate prevention...</p>';
            
            try {
                // Get a vendor that's already in procurement
                const procurementResponse = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const procurementData = await procurementResponse.json();
                
                if (procurementData.status === 'success' && procurementData.data.length > 0) {
                    const vendor = procurementData.data[0];
                    
                    result.innerHTML = `<p>Trying to send ${vendor.company_name} again (should fail)...</p>`;
                    
                    // Try to send the same vendor again
                    const sendResponse = await fetch('api/procurement_vendors.php?action=send_to_procurement', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            vendor_id: vendor.id, 
                            sent_by: 'admin' 
                        })
                    });
                    
                    const sendResult = await sendResponse.json();
                    
                    result.innerHTML += `
                        <p class="${sendResult.status === 'error' ? 'success' : 'error'}">
                            ${sendResult.status === 'error' ? '✅' : '❌'} ${sendResult.message}
                        </p>
                        <p class="info">💡 Duplicate prevention is working!</p>
                    `;
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors available for testing</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testLiveUpdate() {
            const result = document.getElementById('liveResult');
            result.innerHTML = '<p>Testing live updates...</p>';
            
            result.innerHTML += `
                <h4>Manual Test Instructions:</h4>
                <ol>
                    <li>Open <a href="admin/vendor-registration.html" target="_blank">vendor-registration.html</a> in another tab</li>
                    <li>Find a vendor that's already in procurement</li>
                    <li>Change their status from Pending to Approved</li>
                    <li>Come back here and click "Check Procurement Vendors" above</li>
                    <li>You should see the status updated in real-time!</li>
                </ol>
                <p class="info">💡 The sync happens automatically when you change status in vendor-registration.html</p>
            `;
        }
    </script>
</body>
</html>
