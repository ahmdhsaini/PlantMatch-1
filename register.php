<!--Navigation-->
<?php include('public_header.php'); ?>

<!DOCTYPE html>
<html lang="en">
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0">
            <section class="py-5">
                <div class="container px-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-xxl-6">
                            <div class="card shadow border-0 rounded-3 mb-5">
                                <div class="card-body p-5">
                                    <div class="text-center mb-5">
                                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-person-plus"></i></div>
                                        <h2 class="fw-bolder">Create an Account</h2>
                                        <p class="lead fw-normal text-muted mb-0">Join the PlantMatch community today</p>
                                    </div>

                                    <form action="process_register.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="name" name="name" type="text" placeholder="Enter your name..." required />
                                            <label for="name">Full Name</label>
                                        </div>
                                        
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="email" name="email" type="email" placeholder="name@example.com" required />
                                            <label for="email">Email address</label>
                                        </div>
                                        
                                        <div class="form-floating mb-3 position-relative">
                                            <input class="form-control" id="password" name="password" type="password" placeholder="Create a password..." required />
                                            <label for="password">Password</label>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="togglePassword" style="z-index: 10; cursor: pointer;"></i>
                                        </div>

                                        <div class="form-floating mb-3 position-relative">
                                            <input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Retype password..." required />
                                            <label for="confirm_password">Retype Password</label>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="toggleConfirmPassword" style="z-index: 10; cursor: pointer;"></i>
                                        </div>

                                        <div class="d-grid">
                                            <button class="btn btn-primary btn-lg" type="submit">Sign Up</button>
                                        </div>
                                    </form>

                                    <div class="text-center mt-4">
                                        <p class="text-muted">Already have an account? <a href="user_recommendation_form.php">Log in</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <script>
            const setupToggle = (iconId, inputId) => {
                const toggleBtn = document.querySelector(`#${iconId}`);
                const passwordInput = document.querySelector(`#${inputId}`);

                toggleBtn.addEventListener('click', function () {
                    // Toggle the type attribute
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle the icon
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            };

            // Initialize for both fields
            setupToggle('togglePassword', 'password');
            setupToggle('toggleConfirmPassword', 'confirm_password');
        </script>

         <!-- Footer-->
        <?php include('footer.php'); ?>