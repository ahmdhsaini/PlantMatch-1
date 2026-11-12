<?php include('public_header.php'); ?>

<style>
    /* Dark Cyber Crimson/Violet Accent */
    .matrix-accent {
        color: #FF2E93 !important;
        filter: drop-shadow(0 0 15px #FF2E93) drop-shadow(0 0 30px #7B1FA2);
    }

    /* Terrarium Stage Container */
    .terrarium-stage {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* The Glass Biosphere Capsule - Darker, high-contrast borders */
    .glass-capsule {
        position: absolute;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 3px solid #7B1FA2;
        background: radial-gradient(circle at 30% 30%, rgba(255, 46, 147, 0.1) 0%, rgba(123, 31, 162, 0.2) 80%);
        box-shadow: inset 0 0 20px rgba(255, 46, 147, 0.4), 0 0 30px rgba(123, 31, 162, 0.6);
        z-index: 1;
        animation: capsuleVibrate 0.3s linear infinite alternate; /* Aggressive vibration */
    }

    /* Holographic Base Ring - Faster spinning dash array */
    .holo-base {
        position: absolute;
        bottom: 15px;
        width: 110px;
        height: 20px;
        border-radius: 50%;
        background: transparent;
        border: 3px dashed #FF2E93;
        filter: drop-shadow(0 0 10px #FF2E93);
        transform: rotateX(75deg);
        z-index: 0;
        animation: baseSpinFast 1.5s linear infinite; /* Aggressive rotation */
    }

    /* Central Growing Plant Specimen - Rapid scaling & glitch jitter */
    .plant-specimen {
        font-size: 4.2rem;
        z-index: 3;
        transform-origin: bottom center;
        animation: hyperSurge 1.5s ease-in-out infinite alternate; /* Aggressive rapid pulse */
    }

    /* Matrix Digital Stream Particles - High-velocity upward shooting */
    .data-node {
        position: absolute;
        width: 6px;
        height: 6px;
        background-color: #FF2E93;
        border-radius: 50%;
        box-shadow: 0 0 12px #FF2E93, 0 0 20px #7B1FA2;
        opacity: 0;
        z-index: 2;
    }
    .node-1 { left: 45px; animation: rocketUpward 0.8s linear infinite; }
    .node-2 { right: 45px; animation: rocketUpward 1.1s linear infinite 0.3s; }
    .node-3 { left: 50%; animation: rocketUpward 0.6s linear infinite 0.1s; }

    /* Keyframes for Aggressive Tech Motion */
    @keyframes capsuleVibrate {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(2px, -2px) scale(1.02); }
    }

    @keyframes baseSpinFast {
        from { transform: rotateX(75deg) rotate(0deg); }
        to { transform: rotateX(75deg) rotate(360deg); }
    }

    @keyframes hyperSurge {
        0% { transform: scale(0.9) rotate(-4deg); filter: brightness(0.8) drop-shadow(0 0 5px #7B1FA2); }
        100% { transform: scale(1.15) rotate(4deg); filter: brightness(1.3) drop-shadow(0 0 25px #FF2E93); }
    }

    @keyframes rocketUpward {
        0% { transform: translateY(70px) scale(0.4); opacity: 0; }
        20% { opacity: 1; }
        80% { opacity: 1; }
        100% { transform: translateY(-80px) scale(1.5); opacity: 0; }
    }
</style>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h1 class="display-4_ fw-bold" style="color: #104206;">About PlantMatch</h1>
            <p class="lead text-muted">Bringing data-driven insights to your indoor and outdoor gardening journey.</p>
        </div>
        <div class="col-md-6 text-center">
            <div class="terrarium-stage">
                <div class="holo-base"></div>
                <div class="glass-capsule"></div>
                
                <div class="data-node node-1"></div>
                <div class="data-node node-2"></div>
                <div class="data-node node-3"></div>
                
                <i class="bi bi-tree plant-specimen matrix-accent"></i>
            </div>
        </div>
    </div>

    <div class="row g-4 py-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-lg p-4">
                <i class="bi bi-search text-success fs-1 mb-3"></i>
                <h4 class="fw-bold">Database Explorer</h4>
                <p class="text-muted">Browse through thousands of species with detailed care requirements and scientific data.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-lg p-4">
                <i class="bi bi-cpu text-success fs-1 mb-3"></i>
                <h4 class="fw-bold">Smart Algorithm</h4>
                <p class="text-muted">Our system matches your specific environmental light and space constraints to the right plant.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-lg p-4 text-center border-success border-2">
                <i class="bi bi-person-plus fs-1 mb-3 text-success"></i>
                <h4 class="fw-bold">Join Us</h4>
                <p class="text-muted">Register to save your matches and build your own virtual garden collection.</p>
                <a href="register.php" class="btn btn-success mt-auto" style="background-color: #1C7005;">Create Account</a>
            </div>
        </div>
    </div>

    <hr class="my-5 opacity-25"> 
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h2 class="fw-bold mb-4" style="color: #104206;">Meet the Creator</h2>
            
            <div class="card border-0 bg-transparent">
                <div class="card-body">
                    <div class="mb-3">
                        <img src="assets/husaini.jpg" alt="Ahmad Husaini" 
                            class="rounded-circle shadow-sm border border-4 border-white" 
                            style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    
                    <h4 class="fw-bold mb-1">Ahmad Husaini</h4>
                    <p class="text-success fw-semibold mb-3">Lead Developer & Researcher</p>
                    
                    <div class="px-lg-5">
                        <p class="text-muted mx-lg-5">
                            As a Final Year Project student, I developed <strong>PlantMatch</strong> to solve the common 
                            struggle of plant care. By combining botanical data with a user-friendly recommendation 
                            system, my goal is to help everyone from beginners to experts find the right plant 
                            for their unique living space.
                        </p>
                    </div>

                    <div class="mt-4">                        
                        <a href="https://www.linkedin.com/in/ahmadhusaini3" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-linkedin"></i> LinkedIn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5 opacity-25"> 
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
        </div>
    </div>

    <section class="py-5">
        <div class="container px-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: #104206;">Why PlantMatch Matters</h2>
                <p class="text-muted">Our goal is to make urban gardening accessible and successful for everyone.</p>
            </div>
            <div class="row gx-5">
                <div class="col-md-6 mb-4">
                    <div class="p-4 border rounded-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Preventing Plant Loss</h5>
                        <p class="text-muted mb-0 small">Many plants die because they are placed in the wrong environment. PlantMatch reduces this waste by matching biological needs with your actual living conditions.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="p-4 border rounded-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold"><i class="bi bi-house-heart text-success me-2"></i>Urban Optimization</h5>
                        <p class="text-muted mb-0 small">Whether you live in a high-rise apartment or a terrace house, our system identifies species that thrive in specific space and light constraints.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="p-4 border rounded-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold"><i class="bi bi-graph-up-arrow text-success me-2"></i>Data-Driven Care</h5>
                        <p class="text-muted mb-0 small">By moving away from "guesswork" and using a Hybrid Recommender System, we provide confidence to new plant owners who lack gardening experience.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="p-4 border rounded-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold"><i class="bi bi-wind text-success me-2"></i>Better Well-being</h5>
                        <p class="text-muted mb-0 small">Successful indoor gardening improves air quality and mental health. We help you build a sustainable green sanctuary that lasts.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>