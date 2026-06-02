</div> <!-- content -->
</div> <!-- container -->

<script>

function toggleMenu() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

function toggleUserMenu() {
    var dropdown = document.getElementById('userDropdown');
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

document.addEventListener('click', function(event) {
    var userMenu = document.querySelector('.user-menu');
    var dropdown = document.getElementById('userDropdown');

    if (userMenu && !userMenu.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

</script>

</body>
</html>