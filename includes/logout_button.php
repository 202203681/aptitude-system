<?php
// Simple logout button that works anywhere
?>
<style>
.logout-btn-simple {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.logout-btn-simple:hover {
    background: #c82333;
    transform: translateY(-2px);
}
</style>

<button class="logout-btn-simple" onclick="confirmLogout()">
    <i class="fas fa-sign-out-alt"></i> Logout
</button>

<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/logout.php';
    }
}
</script>