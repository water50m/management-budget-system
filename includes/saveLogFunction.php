<?php
function logActivity($conn, $actor_id, $target_id, $action, $desc) {
    $desc = mysqli_real_escape_string($conn, $desc);
    $sql = "INSERT INTO activity_logs (actor_id, target_id, action_type, description) 
            VALUES ($actor_id, $target_id, '$action', '$desc')";
    mysqli_query($conn, $sql);
}
?>