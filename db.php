<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "crowdfunding_db"; // Correct DB name

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if(!$conn){
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>