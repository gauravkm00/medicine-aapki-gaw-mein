    <!-- Footer -->

    <footer class="site-footer">

        <div class="container">

            <div class="row">

                <!-- About -->

                <div class="col-md-6 col-lg-3 mb-4 mb-lg-0">

                    <div class="block-7">

                        <h3 class="footer-heading mb-4">
                            About Us
                        </h3>

                        <p>
                            Medicine Aapki Gaw Mein
                            Forbesganj aur aas-paas ke customers
                            tak medicines pahunchane ki service.
                        </p>

                    </div>

                </div>


                <!-- Quick Links -->

                <div class="col-lg-3 mx-auto mb-5 mb-lg-0">

                    <h3 class="footer-heading mb-4">
                        Quick Links
                    </h3>

                    <ul class="list-unstyled">

                        <li>
                            <a href="index.php">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="medicines.php">
                                Medicines
                            </a>
                        </li>

                        <li>
                            <a href="prescription-upload.php">
                                Upload Prescription
                            </a>
                        </li>

                        <li>
                            <a href="cart.php">
                                Cart
                            </a>
                        </li>

                    </ul>

                </div>


                <!-- Customer -->

                <div class="col-md-6 col-lg-3 mb-4 mb-lg-0">

                    <h3 class="footer-heading mb-4">
                        Customer
                    </h3>

                    <ul class="list-unstyled">

                        <?php if (isset($_SESSION['user_id'])): ?>

                            <li>
                                <a href="orders.php">
                                    My Orders
                                </a>
                            </li>

                            <li>
                                <a href="logout.php">
                                    Logout
                                </a>
                            </li>

                        <?php else: ?>

                            <li>
                                <a href="login.php">
                                    Login
                                </a>
                            </li>

                            <li>
                                <a href="register.php">
                                    Create Account
                                </a>
                            </li>

                        <?php endif; ?>

                    </ul>

                </div>


                <!-- Contact -->

                <div class="col-md-6 col-lg-3">

                    <div class="block-5 mb-5">

                        <h3 class="footer-heading mb-4">
                            Contact Info
                        </h3>

                        <ul class="list-unstyled">

                            <li class="address">
                                Forbesganj, Bihar, India
                            </li>

                            <li class="phone">
                                <a href="tel:+91XXXXXXXXXX">
                                    +91 XXXXXXXXXX
                                </a>
                            </li>

                            <li class="email">
                                support@medicineaapki.in
                            </li>

                        </ul>

                    </div>

                </div>

            </div>


            <!-- Copyright -->

            <div class="row pt-5 mt-5 text-center">

                <div class="col-md-12">

                    <p>

                        Copyright &copy;

                        <script>
                            document.write(
                                new Date().getFullYear()
                            );
                        </script>

                        Medicine Aapki Gaw Mein.

                    </p>

                </div>

            </div>

        </div>

    </footer>

</div>


<!-- JavaScript -->

<script src="assets/js/jquery-3.3.1.min.js"></script>
<script src="assets/js/jquery-ui.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/jquery.magnific-popup.min.js"></script>
<script src="assets/js/aos.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>

