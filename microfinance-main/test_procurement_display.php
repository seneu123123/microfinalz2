<!DOCTYPE html>
<html>
<head>
    <title>Test Procurement Display</title>
</head>
<body>
    <h2>Test Procurement Display Issue</h2>
    
    <h3>1. Check API Response Format</h3>
    <button onclick="testAPIFormat()">Test API Response</button>
    <div id="apiFormat"></div>
    
    <h3>2. Check procurement.php JavaScript</h3>
    <button onclick="testProcurementJS()">Test procurement.js Logic</button>
    <div id="procurementJS"></div>
    
    <h3>3. Manual Display Test</h3>
    <button onclick="testManualDisplay()">Test Manual Display</button>
    <div id="manualDisplay"></div>
    
    <script>
        async function testAPIFormat() {
            const result = document.getElementById('apiFormat');
            result.innerHTML = '<p>Testing API format...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                result.innerHTML = `
                    <h4>API Response Analysis:</h4>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Data Type:</strong> ${typeof data.data}</p>
                    <p><strong>Data Length:</strong> ${data.data ? data.data.length : 'null/undefined'}</p>
                    <p><strong>First Item:</strong></p>
                    <pre>${JSON.stringify(data.data && data.data[0], null, 2)}</pre>
                    <p><strong>Full Response:</strong></p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testProcurementJS() {
            const result = document.getElementById('procurementJS');
            result.innerHTML = '<p>Testing procurement.js logic...</p>';
            
            try {
                // Simulate the procurement.php loadVendors function
                const apiUrl = 'api/procurement_vendors.php';
                console.log('Loading vendors from logistics_db...');
                
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

                const result = await response.json();
                console.log('API Response:', result);

                if (result.status === 'success') {
                    const vendors = result.data || [];
                    console.log('Loaded vendors from logistics_db:', vendors.length);
                    
                    result.innerHTML = `
                        <h4>procurement.js Logic Test:</h4>
                        <p><strong>Response OK:</strong> ✅</p>
                        <p><strong>Status Success:</strong> ✅</p>
                        <p><strong>Vendors Loaded:</strong> ${vendors.length}</p>
                        <p><strong>Sample Vendor:</strong></p>
                        <pre>${JSON.stringify(vendors[0], null, 2)}</pre>
                    `;
                } else {
                    result.innerHTML = `<p style="color: red;">❌ API Error: ${result.message}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testManualDisplay() {
            const result = document.getElementById('manualDisplay');
            result.innerHTML = '<p>Testing manual display...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    // Simulate the displayVendors function from procurement.php
                    const tbody = data.data.map(vendor => `
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
                                <span class="status-badge">${vendor.status}</span>
                            </td>
                        </tr>
                    `).join('');
                    
                    result.innerHTML = `
                        <h4>Manual Display Test:</h4>
                        <p><strong>Vendors Found:</strong> ${data.data.length}</p>
                        <table border="1" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>Vendor ID</th>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Contact Info</th>
                                    <th>Business Type</th>
                                    <th>Business Details</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tbody}
                            </tbody>
                        </table>
                        <p style="color: green;">✅ Manual display works! The issue is in procurement.php JavaScript.</p>
                    `;
                } else {
                    result.innerHTML = `
                        <h4>Manual Display Test:</h4>
                        <p style="color: orange;">⚠️ No vendors to display</p>
                        <p>Response: ${JSON.stringify(data)}</p>
                    `;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">❌ Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
