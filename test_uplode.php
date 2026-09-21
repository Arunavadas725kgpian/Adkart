<?php
include 'db.php';

echo "<h2>Image Upload & DB Debugger</h2>";

// ১. image ফোল্ডার চেক
$folder = __DIR__ . '/image/';
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
    echo "<p style='color:green;'>✓ 'image' ফোল্ডার তৈরি করা হয়েছে।</p>";
} else {
    echo "<p style='color:green;'>✓ 'image' ফোল্ডার উপস্থিত আছে।</p>";
}

// ২. আপলোড টেস্ট ফর্ম সাবমিশন
if (isset($_POST['test_submit'])) {
    $p_id = intval($_POST['p_id']);
    
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === 0) {
        $ext = pathinfo($_FILES['test_file']['name'], PATHINFO_EXTENSION);
        $new_name = "item_" . $p_id . "_" . time() . "." . $ext;
        $dest = $folder . $new_name;

        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $dest)) {
            echo "<p style='color:green;'>✓ ফাইলে ছবি সেভ সফল: <b>$new_name</b></p>";
            
            // ডাটাবেস আপডেট
            $update = mysqli_query($conn, "UPDATE products SET image = '$new_name' WHERE id = $p_id OR ID = $p_id");
            if ($update) {
                echo "<p style='color:green;'>✓ ডাটাবেসে নাম আপডেট সফল!</p>";
                echo "<p>ছবিটি দেখুন:<br><img src='image/$new_name' style='max-width:150px; border:2px solid green;'></p>";
                echo "<p><a href='index.php'>হোমপেজে গিয়ে দেখুন</a></p>";
            } else {
                echo "<p style='color:red;'>✗ ডাটাবেস আপডেট ব্যর্থ: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color:red;'>✗ ফোল্ডারে ফাইল সেভ হতে ব্যর্থ হয়েছে।</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ ফাইল আপলোডে সমস্যা: Error Code " . $_FILES['test_file']['error'] . "</p>";
    }
}

// ৩. ডাটাবেসের বর্তমান অবস্থা দেখানো
echo "<hr><h3>বর্তমানে ডাটাবেসে থাকা প্রোডাক্ট ও তাদের ছবির তালিকা:</h3>";
$q = mysqli_query($conn, "SELECT * FROM products");
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Image Column Value</th><th>Action</th></tr>";

while ($row = mysqli_fetch_assoc($q)) {
    $id = isset($row['ID']) ? $row['ID'] : $row['id'];
    $name = isset($row['Name']) ? $row['Name'] : $row['name'];
    $img = isset($row['image']) ? $row['image'] : 'কলাম নেই বা খালি';
    
    echo "<tr>";
    echo "<td>$id</td>";
    echo "<td>$name</td>";
    echo "<td><b>$img</b></td>";
    echo "<td>
            <form action='test_upload.php' method='POST' enctype='multipart/form-data'>
                <input type='hidden' name='p_id' value='$id'>
                <input type='file' name='test_file' required>
                <input type='submit' name='test_submit' value='Upload'>
            </form>
          </td>";
    echo "</tr>";
}
echo "</table>";
?>