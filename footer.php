</div><!-- end main-content -->

<div class="page-footer">
    <span>© 2024 HRD Payroll System. All rights reserved.</span>
    <span>Version 1.0.0</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
    document.querySelector('.topbar').classList.toggle('expanded');
}
function confirmDelete(url, name) {
    if(confirm('Yakin ingin menghapus ' + name + '?')) {
        window.location.href = url;
    }
}
</script>
</body>
</html>
