<!DOCTYPE html>
<html>
<head>
    <title>Test Fixed API Final</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Test Fixed API with Real Database</h1>
    
    <h2>Step 1: Test Get Procurement Vendors (Fixed API)</h2>
    <button onclick="testGetAPI()">Test Get API</button>
    <div id="getResult"></div>
    
    <h2>Step 2: Test procurement.php Simulation</h2>
    <button onclick="testProcurementSimulation()">Test procurement.php Logic</button>
    <div id="procurementResult"></div>
    
    <h2>Step 3: Open procurement.php</h2>
    <button onclick="openProcurementPage()">Open procurement.php</button>
    <div id="openResult"></div>

    <script>
        async function testGetAPI() {
            const result = document.getElementById('getResult');
            result.innerHTML = '<p>Testing fixed API...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const data = await response.json();
                
                result.innerHTML = `
                    <h3>API Response:</h3>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Vendors Found:</strong> ${data.data ? data.data.length : 0}</p>
                    
                    ${data.data && data.data.length > 0 ? `
                        <h4>Vendors in procurement table:</h4>
                        <table>
                            <tr><th>ID</th><th>Company Name</th><th>Email</th><th>Phone</th><th>Business Type</th><th>Status</th><th>Sent At</th></tr>
                            ${data.data.map(v => `
                                <tr>
                                    <td><strong>${v.id}</strong></td>
                                    <td>${v.company_name}</td>
                                    <td>${v.email}</td>
                                    <td>${v.phone || '-'}</td>
                                    <td>${v.business_type || '-'}</td>
                                    <td><span style="background: #e8f5e8; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${v.status}</span></td>
                                    <td>${v.sent_at}</td>
                                </tr>
                            `).join('')}
                        </table>
                        
                        <h4>Raw API Response:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        
                        <p class="success">✅ API is working correctly with the database!</p>
                    ` : '<p class="error">❌ No vendors found in procurement table</p>'}
                `;
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        async function testProcurementSimulation() {
            const result = document.getElementById('procurementResult');
            result.innerHTML = '<p>Simulating procurement.php logic...</p>';
            
            try {
                // Simulate the exact logic from procurement.php
                const apiUrl = '../api/procurement_vendors.php';
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
                            <p>But we know there are vendors in the database. Let's check why...</p>
                        `;
                    } else {
                        // Simulate the displayVendors function from procurement.php
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
                            <p class="success">✅ procurement.php should display ${vendors.length} vendors!</p>
                            <p>Here's what the table would look like:</p>
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
                            <p class="success">🎉 procurement.php should work correctly now!</p>
                        `;
                    }
                } else {
                    result.innerHTML = `<p class="error">❌ API Error: ${apiResult.message}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p class="error">❌ Error: ${error.message}</p>`;
            }
        }
        
        function openProcurementPage() {
            const result = document.getElementById('openResult');
            result.innerHTML = `
                <p>Opening procurement.php in a new tab...</p>
                <p><strong>Expected Result:</strong> Should now show the vendors!</p>
                <button onclick="window.open('admin/procurement.php', '_blank')" style="background: #2ca078; color: white; padding: 10px 20px;">
                    🚀 Open procurement.php
                </button>
                <p>If procurement.php still shows "No vendors sent to procurement yet", there might be a browser cache issue. Try refreshing with Ctrl+F5.</p>
            `;
        }
    </script>
</body>
</html>
