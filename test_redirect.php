<?php
// Simple test to verify redirect behavior
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirect Test</title>
</head>
<body>
    <h1>Redirect Test</h1>
    <p>Testing redirect to admin dashboard...</p>
    
    <button onclick="testRedirect()">Test Redirect to Dashboard</button>
    
    <script>
        function testRedirect() {
            console.log('Testing redirect to admin/dashboard.php');
            window.location.href = 'admin/dashboard.php';
        }
        
        // Also test on page load
        setTimeout(() => {
            console.log('Auto-redirecting to admin/dashboard.php in 2 seconds...');
            window.location.href = 'admin/dashboard.php';
        }, 2000);
    </script>
</body>
</html>
