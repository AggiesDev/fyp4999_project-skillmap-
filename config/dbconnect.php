<?php
$conn = mysqli_connect("localhost", "root", "", "skill_map_system");

if (!$conn) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
