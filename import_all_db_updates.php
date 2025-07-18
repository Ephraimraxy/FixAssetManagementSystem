<?php
// Database connection
$host = 'localhost';
$db = 'fams_db';  // Your database name
$user = 'root';   // Your database username
$pass = '';       // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>Connected to database successfully</h2>";
    
    // SQL files to import
    $sqlFiles = [
        'fams_master.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        if (file_exists($file)) {
            echo "<h3>Importing $file...</h3>";
            
            // Read the SQL file
            $sql = file_get_contents($file);
            
            // Split the SQL file on semicolons to get individual queries
            $queries = explode(';', $sql);
            
            // Execute each query
            $successCount = 0;
            $failCount = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                        $successCount++;
                    } catch (PDOException $e) {
                        echo "<div style='color: red; margin-bottom: 5px;'>Error in query: " . htmlspecialchars($query) . " - " . $e->getMessage() . "</div>";
                        $failCount++;
                    }
                }
            }
            
            echo "<div style='color: green;'>Successfully executed $successCount queries</div>";
            if ($failCount > 0) {
                echo "<div style='color: orange;'>Failed to execute $failCount queries (possibly because structures already exist)</div>";
            }
        } else {
            echo "<div style='color: red;'>File $file not found</div>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>Import Complete!</h2>";
    echo "<p>You can now <a href='../index.php'>go to the login page</a> to access your application.</p>";
    
} catch (PDOException $e) {
    die("<div style='color: red;'>Connection failed: " . $e->getMessage() . "</div>");
}
?>
