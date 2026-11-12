<!--Navigation-->
<?php include('public_header.php'); ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Login - PlantMatch</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0">

            <section class="py-5">
                <div class="container px-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-xxl-6">
                            <div class="card shadow border-0 rounded-3 mb-5">
                                <div class="card-body p-5">
                                    <div class="text-center mb-5">
                                        <div class="feature bg-success bg-gradient text-white rounded-3 mb-3"><i class="bi bi-unlock"></i></div>
                                        <h2 class="fw-bolder">Welcome Back</h2>
                                        <p class="lead fw-normal text-muted mb-0">Login to your account</p>
                                    </div>

                                    <form action="process_login.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="email" name="email" type="email" placeholder="name@example.com" required />
                                            <label for="email">Email address</label>
                                        </div>
                                        
                                        <div class="form-floating mb-3 position-relative">
                                            <input class="form-control" id="password" name="password" type="password" placeholder="Enter password..." required />
                                            <label for="password">Password</label>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="togglePassword" style="z-index: 10; cursor: pointer;"></i>
                                        </div>

                                        <div class="d-grid">
                                            <button class="btn btn-success btn-lg" type="submit">Login</button>
                                        </div>
                                    </form>

                                    <div class="text-center mt-4">
                                        <p class="text-muted">New user? <a href="register.php">Create an account</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <?php include('footer.php'); ?>

        <script>
            const toggleBtn = document.querySelector('#togglePassword');
            const passwordInput = document.querySelector('#password');

            toggleBtn.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        </script>
    </body>
</html>