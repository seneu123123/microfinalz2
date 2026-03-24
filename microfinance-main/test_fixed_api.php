<!DOCTYPE html>
<html>
<head>
    <title>Test Fixed API</title>
</head>
<body>
    <h2>Test Fixed Procurement API</h2>
    
    <h3>Test Send to Procurement (Fixed API)</h3>
    <button onclick="testFixedAPI()">Test Fixed Send API</button>
    <div id="fixedResult"></div>
    
    <h3>Test Get Procurement</h3>
    <button onclick="testGetAPI()">Test Get API</button>
    <div id="getResult"></div>
    
    <h3>Current Procurement Table</h3>
    <button onclick="showCurrentTable()">Show Current Table</button>
    <div id="tableResult"></div>
    
    <script>
        async function testFixedAPI() {
            const result = document.getElementById('fixedResult');
            result.innerHTML = '<p>Testing fixed API...</p>';
            
            try {
                // Get a vendor ID first
                const vendorsResponse = await fetch('api/vendors.php');
                const vendorsData = await vendorsResponse.json();
                
                if (vendorsData.success && vendorsData.data && vendorsData.data.length > 0) {
                    const vendor = vendorsData.data[0];
                    
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
                    
                    result.innerHTML = `
                        <h4>Fixed API Test Result:</h4>
                        <p><strong>HTTP Status:</strong> ${response.status}</p>
                        <p><strong>API Status:</strong> ${data.status}</p>
                        <p><strong>Message:</strong> ${data.message}</p>
                        <p><strong>Vendor:</strong> ${vendor.vendor_name} (${vendor.vendor_id})</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                    
                    if (data.status === 'success') {
                        result.innerHTML += '<p style="color: green;">✅ API is now working!</p>';
                    } else {
                        result.innerHTML += '<p style="color: red;">❌ API still has issues</p>';
                    }
                } else {
                    result.innerHTML = '<p style="color: red;">❌ No vendors available</p>';
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testGetAPI() {
            const result = document.getElementById('getResult');
            result.innerHTML = '<p>Testing Get API...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                result.innerHTML = `
                    <h4>Get API Result:</h4>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Vendors Found:</strong> ${data.data ? data.data.length : 0}</p>
                    ${data.data && data.data.length > 0 ? `
                        <table border="1" style="width: 100%;">
                            <tr><th>ID</th><th>Name</th><th>Email</th><th>Business Type</th></tr>
                            ${data.data.slice(0, 5).map(v => `
                                <tr>
                                    <td>${v.id}</td>
                                    <td>${v.company_name}</td>
                                    <td>${v.email}</td>
                                    <td>${v.business_type}</td>
                                </tr>
                            `).join('')}
                        </table>
                    ` : '<p>No vendors in procurement table</p>'}
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function showCurrentTable() {
            const result = document.getElementById('tableResult');
            result.innerHTML = '<p>Loading current table...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                if (data.status === 'success' && data.data) {
                    result.innerHTML = `
                        <h4>Current Procurement Table:</h4>
                        <p><strong>Total Vendors:</strong> ${data.data.length}</p>
                        <table border="1" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Business Type</th>
                                    <th>Business Details</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.map(vendor => `
                                    <tr>
                                        <td><strong>${vendor.id}</strong></td>
                                        <td>${vendor.company_name}</td>
                                        <td>${vendor.contact_person || '-'}</td>
                                        <td>${vendor.email}</td>
                                        <td>${vendor.phone || '-'}</td>
                                        <td>${vendor.business_type || '-'}</td>
                                        <td><small>${vendor.business_details || '-'}</small></td>
                                        <td><span class="status-badge">${vendor.status}</span></td>
                                        <td>${vendor.sent_at}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    result.innerHTML = '<p>No vendors in procurement table</p>';
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
