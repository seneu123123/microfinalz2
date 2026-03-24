<!DOCTYPE html>
<html>
<head>
    <title>Test 500 Error</title>
</head>
<body>
    <h2>Test HTTP 500 Error</h2>
    
    <h3>1. Test Direct File Access</h3>
    <a href="admin/vendor-registration.html" target="_blank">Open vendor-registration.html</a><br><br>
    
    <h3>2. Test API Endpoints</h3>
    <button onclick="testVendorsAPI()">Test vendors.php</button><br><br>
    <button onclick="testProcurementAPI()">Test procurement_vendors.php</button><br><br>
    
    <div id="results"></div>
    
    <h3>3. Browser Console</h3>
    <p>Open browser console (F12) to see any JavaScript errors</p>
    
    <script>
        async function testVendorsAPI() {
            const results = document.getElementById('results');
            results.innerHTML = '<p>Testing vendors.php...</p>';
            
            try {
                const response = await fetch('api/vendors.php');
                const text = await response.text();
                
                results.innerHTML = `
                    <h4>vendors.php Response:</h4>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Response:</strong></p>
                    <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">${text}</pre>
                `;
            } catch (error) {
                results.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            }
        }
        
        async function testProcurementAPI() {
            const results = document.getElementById('results');
            results.innerHTML = '<p>Testing procurement_vendors.php...</p>';
            
            try {
                const response = await fetch('api/procurement_vendors.php?action=get_procurement_vendors');
                const text = await response.text();
                
                results.innerHTML = `
                    <h4>procurement_vendors.php Response:</h4>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Response:</strong></p>
                    <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">${text}</pre>
                `;
            } catch (error) {
                results.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
