<!DOCTYPE html>
<html>
<head>
    <title>Simple Debug</title>
</head>
<body>
    <h2>Simple Procurement Debug</h2>
    
    <h3>Step 1: Test Database Connection</h3>
    <button onclick="testDatabase()">Test Database</button>
    <div id="dbResult"></div>
    
    <h3>Step 2: Test Vendors Table</h3>
    <button onclick="testVendors()">Test Vendors</button>
    <div id="vendorsResult"></div>
    
    <h3>Step 3: Test Procurement Table</h3>
    <button onclick="testProcurement()">Test Procurement</button>
    <div id="procurementResult"></div>
    
    <h3>Step 4: Test Send to Procurement</h3>
    <button onclick="testSendProcurement()">Test Send API</button>
    <div id="sendResult"></div>
    
    <h3>Step 5: Test Get Procurement</h3>
    <button onclick="testGetProcurement()">Test Get API</button>
    <div id="getResult"></div>
    
    <script>
        async function testDatabase() {
            const result = document.getElementById('dbResult');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const text = await response.text();
                
                if (response.status === 200) {
                    result.innerHTML = '<p style="color: green;">✅ Database connection works</p>';
                } else {
                    result.innerHTML = `<p style="color: red;">❌ HTTP ${response.status}: ${text}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testVendors() {
            const result = document.getElementById('vendorsResult');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/vendors.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    result.innerHTML = `
                        <p style="color: green;">✅ Found ${data.data.length} vendors</p>
                        <table border="1">
                            <tr><th>ID</th><th>Name</th></tr>
                            ${data.data.slice(0, 3).map(v => `<tr><td>${v.vendor_id}</td><td>${v.vendor_name}</td></tr>`).join('')}
                        </table>
                    `;
                } else {
                    result.innerHTML = `<p style="color: red;">❌ No vendors found</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testProcurement() {
            const result = document.getElementById('procurementResult');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                if (data.success && data.data) {
                    result.innerHTML = `
                        <p style="color: green;">✅ Found ${data.data.length} vendors in procurement</p>
                        <table border="1">
                            <tr><th>ID</th><th>Name</th><th>Sent At</th></tr>
                            ${data.data.slice(0, 3).map(v => `<tr><td>${v.id}</td><td>${v.company_name}</td><td>${v.sent_at}</td></tr>`).join('')}
                        </table>
                    `;
                } else {
                    result.innerHTML = `<p style="color: orange;">⚠️ No vendors in procurement yet</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testSendProcurement() {
            const result = document.getElementById('sendResult');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                // First get a vendor ID
                const vendorsResponse = await fetch('api/vendors.php');
                const vendorsData = await vendorsResponse.json();
                
                if (vendorsData.success && vendorsData.data && vendorsData.data.length > 0) {
                    const vendorId = vendorsData.data[0].vendor_id;
                    
                    const response = await fetch('api/procurement_vendors.php?action=send_to_procurement', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            vendor_id: vendorId,
                            sent_by: 'admin'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        result.innerHTML = `<p style="color: green;">✅ ${data.message}</p>`;
                    } else {
                        result.innerHTML = `<p style="color: red;">❌ ${data.message}</p>`;
                    }
                } else {
                    result.innerHTML = `<p style="color: red;">❌ No vendors available to test</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testGetProcurement() {
            const result = document.getElementById('getResult');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                result.innerHTML = `
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Vendors:</strong> ${data.data ? data.data.length : 0}</p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
