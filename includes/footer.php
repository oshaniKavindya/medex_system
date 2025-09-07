</div> <!-- Close container -->
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Faculty of Geomatics, Sabaragamuwa University of Sri Lanka</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Medical Excuse Management System v1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/medical_excuse_system/assets/js/main.js"></script>
    
    <?php if (isset($extraFooter)) echo $extraFooter; ?>
</body>
</html>