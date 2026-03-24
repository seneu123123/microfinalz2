<!DOCTYPE html>
<html>
<head>
    <title>Complete Workflow Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Complete Procurement Workflow Test</h1>
    
    <div class="section">
        <h2>Step 1: Check Source Vendors</h2>
        <button onclick="checkSourceVendors()">Check Source Vendors</button>
        <div id="sourceResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 2: Check Procurement Vendors</h2>
        <button onclick="checkProcurementVendors()">Check Procurement Vendors</button>
        <div id="procurementResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 3: Send Vendor to Procurement</h2>
        <button onclick="sendToProcurement()">Send Vendor to Procurement</button>
        <div id="sendResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 4: Verify in procurement.php</h2>
        <button onclick="verifyInProcurement()">Verify in procurement.php</button>
        <div id="verifyResult"></div>
    </div>
    
    <div class="section">
        <h2>Step 5: Test procurement.php Directly</h2>
        <button onclick="testProcurementPage()">Test procurement.php</button>
        <div id="testResult"></div>
    </div>

    <script>
        async function checkSourceVendors() {
            const result = document.getElementById('sourceResult');
            result.innerHTML = '<p>Checking source vendors...</p>';
            
            try {
                const response = await fetch('api/vendors.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    result.innerHTML = `
                        <p class="success">✅ Found ${data.data.length} vendors in source table</p>
                        <table>
                            <tr><th>ID</th><th>Name</th><th>Email</th><th>Business Type</th></tr>
                            ${data.data.slice(0, 5).map(v => `
                                <tr>
                                    <td>${v.vendor_id}</td>
                                    <td>${v.vendor_name}</td>
                                    <td>${v.email}</td>
                                    <td>${v.business_type || '-'}</td>
                                </tr>
                            `).join('')}
                        </table>
                    `;
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors found in source table</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function checkProcurementVendors() {
            const result = document.getElementById('procurementResult');
            result.innerHTML = '<p>Checking procurement vendors...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                result.innerHTML = `
                    <p class="info">API Status: ${data.status}</p>
                    <p>Vendors in procurement: <strong>${data.data ? data.data.length : 0}</strong></p>
                    ${data.data && data.data.length > 0 ? `
                        <table>
                            <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Sent At</th></tr>
                            ${data.data.map(v => `
                                <tr>
                                    <td>${v.id}</td>
                                    <td>${v.company_name}</td>
                                    <td>${v.email}</td>
                                    <td>${v.status}</td>
                                    <td>${v.sent_at}</td>
                                </tr>
                            `).join('')}
                        </table>
                    ` : '<p class="error">❌ No vendors in procurement table</p>'}
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function sendToProcurement() {
            const result = document.getElementById('sendResult');
            result.innerHTML = '<p>Sending vendor to procurement...</p>';
            
            try {
                // Get first vendor
                const vendorsResponse = await fetch('api/vendors.php');
                const vendorsData = await vendorsResponse.json();
                
                if (vendorsData.success && vendorsData.data && vendorsData.data.length > 0) {
                    const vendor = vendorsData.data[0];
                    
                    result.innerHTML = `<p>Attempting to send: ${vendor.vendor_name} (${vendor.vendor_id})</p>`;
                    
                    const response = await fetch('api/procurement_vendors.php?action=send_to_procurement', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            vendor_id: vendor.vendor_id,
                            sent_by: 'admin'
                        })
                    });
                    
                    const data = await response.json();
                    
                    result.innerHTML += `
                        <p class="${data.status === 'success' ? 'success' : 'error'}">
                            ${data.status === 'success' ? '✅' : '❌'} ${data.message}
                        </p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                    
                    if (data.status === 'success') {
                        // Auto-refresh procurement vendors
                        setTimeout(() => checkProcurementVendors(), 1000);
                    }
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors available to send</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function verifyInProcurement() {
            const result = document.getElementById('verifyResult');
            result.innerHTML = '<p>Verifying vendor appears in procurement...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    result.innerHTML = `
                        <p class="success">✅ Found ${data.data.length} vendors in procurement!</p>
                        <p>These vendors should appear in procurement.php</p>
                        <table>
                            <tr><th>ID</th><th>Company Name</th><th>Contact</th><th>Email</th><th>Business Type</th><th>Status</th></tr>
                            ${data.data.map(v => `
                                <tr>
                                    <td>${v.id}</td>
                                    <td>${v.company_name}</td>
                                    <td>${v.contact_person || '-'}</td>
                                    <td>${v.email}</td>
                                    <td>${v.business_type || '-'}</td>
                                    <td><span style="background: #e8f5e8; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${v.status}</span></td>
                                </tr>
                            `).join('')}
                        </table>
                        <p class="info">💡 If you see vendors here but not in procurement.php, there might be a JavaScript error in procurement.php</p>
                    `;
                } else {
                    result.innerHTML = '<p class="error">❌ No vendors found in procurement table</p>';
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testProcurementPage() {
            const result = document.getElementById('testResult');
            result.innerHTML = '<p>Testing procurement.php JavaScript...</p>';
            
            try {
                // Simulate the exact same logic as procurement.php
                const apiUrl = 'api/procurement_vendors.php';
                const response = await fetch(`${apiUrl}?action=get_procurement_vendors`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const apiResult = await response.json();
                console.log('API Response:', apiResult);

                if (apiResult.status === 'success') {
                    const vendors = apiResult.data || [];
                    console.log('Loaded vendors:', vendors.length);
                    
                    if (vendors.length === 0) {
                        result.innerHTML = `
                            <p class="error">❌ procurement.php would show "No vendors sent to procurement yet"</p>
                            <p>This is because the API returned 0 vendors.</p>
                        `;
                    } else {
                        // Simulate the table display
                        const tbody = vendors.map(vendor => `
                            <tr>
                                <td><strong>${vendor.id}</strong></td>
                                <td>${vendor.company_name}</td>
                                <td>${vendor.contact_person}</td>
                                <td>
                                    <div>${vendor.email}</div>
                                    <small>${vendor.phone}</small>
                                </td>
                                <td>${vendor.business_type || '-'}</td>
                                <td><small>${vendor.business_details || '-'}</small></td>
                                <td>
                                    <span style="background: #e8f5e8; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                                        ${vendor.status}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                        
                        result.innerHTML = `
                            <p class="success">✅ procurement.php would display ${vendors.length} vendors!</p>
                            <table style="border-collapse: collapse; width: 100%;">
                                <thead>
                                    <tr style="background: #f2f2f2;">
                                        <th>Vendor ID</th>
                                        <th>Company Name</th>
                                        <th>Contact Person</th>
                                        <th>Contact Info</th>
                                        <th>Business Type</th>
                                        <th>Business Details</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>${tbody}</tbody>
                            </table>
                            <p class="success">🎉 procurement.php should work correctly!</p>
                        `;
                    }
                } else {
                    result.innerHTML = `<p class="error">❌ API Error: ${apiResult.message}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
