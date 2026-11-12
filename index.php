<?php include('public_header.php'); ?>

<main class="flex-shrink-0 overflow-hidden">

    <header class="py-5" style="background-color: #ffffff;">
        <div class="container px-5">
            <div class="row gx-5 align-items-center justify-content-center">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <div class="my-5 text-center text-xl-start">
                        <h1 class="display-5 fw-bolder text-black mb-2">PlantMatch</h1>
                        <p class="lead fw-normal text-black mb-4">Choose Plant that suitable to your environment.</p>
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center justify-content-xl-start">
                            <a class="btn btn-lg px-4 me-sm-3" style="color: #FFFFFF; background-color: #5f7736;" href="search.php">Search Plants</a>
                            <a class="btn btn-outline-dark btn-lg px-4" href="#features">Learn More</a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-xxl-6 d-none d-xl-block text-center">
                    <img class="img-fluid rounded-3 my-5" 
                         src="assets/gardener1.jpg" 
                         alt="Gardener at work" 
                         style="box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);">
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="row justify-content-center my-5">
            <div class="col-20 col-md-38 col-lg-25">
                <section class="py-5" id="features" style="background-color: #ffffff;"></section>
                <hr class="opacity-25" style="border-top: 2px solid #000;">
            </div>
        </div>
    </div>

        <div class="container px-5">
            <div class="row gx-5">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h2 class="fw-bolder mb-0">Personalized Recommendations for Every Space.</h2><br>
                    <p class="lead fw-normal text mb-0">Using environmental data and user preferences
                        to generate a ranked list of plants that will thrive in your specific care.</p>

                    <style>
                        .eco-node-stage {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 100%;
                            margin-top: 40px; /* Adjust spacing as needed */
                        }
                        
                        .eco-pulse-stage {
                            position: relative;
                            width: 140px; /* Significantly Enlarged Stage */
                            height: 140px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        /* Expanding central radar ripple wave */
                        .eco-pulse-wave {
                            position: absolute;
                            width: 100%;
                            height: 100%;
                            border-radius: 50%;
                            background-color: #00f2fe;
                            opacity: 0;
                            animation: ecoPulseRadar 2s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;
                        }
                        
                        .eco-pulse-delayed {
                            animation-delay: 1s;
                        }

                        /* Continuous rotating tracking orbit rings */
                        .tracking-orbit-ring {
                            position: absolute;
                            width: 70%;
                            height: 70%;
                            border: 2px dashed rgba(127, 0, 255, 0.2);
                            border-radius: 50%;
                            animation: rotateEcoNode 6s linear infinite;
                        }
                        
                        /* Opposing faster interior ring */
                        .inner-orbit-ring {
                            width: 50%;
                            height: 50%;
                            border: 1px dotted rgba(0, 242, 254, 0.3);
                            animation: rotateEcoNodeCounter 3s linear infinite;
                        }

                        /* The central glowing matrix core */
                        .matrix-core-dot {
                            position: relative;
                            width: 32px; /* Substantial core dot */
                            height: 32px;
                            background: linear-gradient(135deg, #00f2fe 0%, #7f00ff 100%);
                            border-radius: 50%;
                            z-index: 2;
                            box-shadow: 0 0 20px rgba(0, 242, 254, 0.8);
                            animation: coreNodeFlicker 1s ease-in-out infinite alternate;
                        }

                        @keyframes ecoPulseRadar {
                            0% { transform: scale(0.3); opacity: 0.9; }
                            100% { transform: scale(1.1); opacity: 0; }
                        }

                        @keyframes rotateEcoNode {
                            from { transform: rotate(0deg); }
                            to { transform: rotate(360deg); }
                        }
                        
                        @keyframes rotateEcoNodeCounter {
                            from { transform: rotate(360deg); }
                            to { transform: rotate(0deg); }
                        }

                        @keyframes coreNodeFlicker {
                            0% { transform: scale(0.9); opacity: 0.7; }
                            100% { transform: scale(1.05); opacity: 1; box-shadow: 0 0 25px rgba(0, 242, 254, 0.9); }
                        }
                    </style>
                    
                    <div class="eco-node-stage">
                        <div class="eco-pulse-stage">
                            <div class="eco-pulse-wave"></div>
                            <div class="eco-pulse-wave eco-pulse-delayed"></div>
                            <div class="tracking-orbit-ring"></div>
                            <div class="tracking-orbit-ring inner-orbit-ring"></div>
                            <div class="matrix-core-dot"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="row gx-5 row-cols-1 row-cols-md-2">
                        <div class="col mb-5 h-100">
                            <div class="feature bg-gradient text-white rounded-3 mb-3 p-2 d-inline-block" style="background-color: #d1a153;"><i class="bi bi-flower2"></i></div>
                            <h2 class="h5">Environmental Assessment</h2>
                            <p class="mb-0"><b>Input Conditions.</b> Users provide details about their space, such as sunlight levels and 
                                humidity, which the system uses as the foundation for the recommendation algorithm.</p>
                        </div>
                        <div class="col mb-5 h-100">
                            <div class="feature bg-gradient text-white rounded-3 mb-3 p-2 d-inline-block" style="background-color: #d1a153;"><i class="bi bi-tree"></i></div>
                            <h2 class="h5">Ranked Recommendations</h2>
                            <p class="mb-0"><b>Personalized Ranking.</b> The system calculates a compatibility score for each plant, 
                                providing a ranked list of species that best fit the user's specific environmental profile.</p>
                        </div>
                        <div class="col mb-5 mb-md-0 h-100">
                            <div class="feature bg-gradient text-white rounded-3 mb-3 p-2 d-inline-block" style="background-color: #d1a153;"><i class="bi bi-flower3"></i></div>
                            <h2 class="h5">Species Compatibility</h2>
                            <p class="mb-0"><b>Data-Driven Matching.</b> By comparing user requirements against our plant database, 
                                the system identifies the most suitable species to prevent plant-owner mismatch.</p>
                        </div>
                        <div class="col h-100">
                            <div class="feature bg-gradient text-white rounded-3 mb-3 p-2 d-inline-block" style="background-color: #d1a153;"><i class="bi bi-sun"></i></div>
                            <h2 class="h5">Decision Support</h2>
                            <p class="mb-0"><b>Informed Selection.</b> Helps users choose their next plant with confidence by highlighting 
                                why a specific species is recommended for their experience level and environment.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row justify-content-center my-5">
            <div class="col-20 col-md-38 col-lg-25">
                <hr class="opacity-25" style="border-top: 2px solid #000;">
            </div>
        </div>
    </div>

    <section class="py-5" style="background-color: #ffffff">
        <div class="container px-5">
            <div class="row gx-5 justify-content-center">
                <div class="col-lg-10 col-xl-7 text-center">
                    <div class="fs-4 mb-4 fst-italic">"Developing the recommendation algorithm allowed me to bridge 
                        the gap between complex botanical data and a simple user experience. This project simplifies 
                        plant ownership for people with busy urban lifestyles."</div>
                    <div class="d-flex align-items-center justify-content-center">
                        <img class="rounded-circle me-3" src="assets/husaini.jpg" alt="Profile Picture" style="width: 40px; height: 40px; object-fit: cover;"/>
                        <div class="fw-bold">
                            Ahmad Husaini
                            <span class="fw-bold text-primary mx-1">/</span>
                            Creator of PlantMatch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row justify-content-center my-5">
            <div class="col-20 col-md-38 col-lg-25">
                <hr class="opacity-25" style="border-top: 2px solid #000;">
            </div>
        </div>
    </div>

    <section class="game-section py-5" style="background-color: #f8f9fa;">
    <style>
        .btn-game-select {
            background-color: #ffffff;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }
        .btn-game-select.active {
            background-color: #198754 !important;
            border-color: #146c43 !important;
            box-shadow: inset 0 4px 6px rgba(0,0,0,0.2) !important;
        }
        .btn-game-select.active small, .btn-game-select.active span {
            color: #ffffff !important;
        }
        .canvas-wrapper {
            border: 4px solid #198754;
            display: inline-block;
            line-height: 0;
            background-color: #2d5a27;
        }
        /* Style for the overlay when game is stopped */
        .game-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            pointer-events: none;
        }
    </style>

    <div class="container text-center">
        <h2 class="mb-2 fw-bold" style="color: #198754;">Botanical Defense</h2>
        <p class="text-secondary small mb-4">Protect the Garden! | Goal: 1000 Points</p>
        
        <div class="row justify-content-center g-3 mb-4">
            <div class="col-md-3">
                <div class="p-2 bg-white rounded border shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                    <h5 class="mb-1 text-dark fw-bold">☀️ Sun: <span id="sunCount" class="text-warning">250</span></h5>
                    <h5 class="mb-1 text-dark small">Score: <span id="gameScore" class="text-primary">0</span>/1000</h5>
                    <span id="gameStatus" class="badge bg-secondary mt-1">Ready</span>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="btn-group w-100 shadow-sm h-100">
                    <button class="btn btn-game-select active py-2" id="btn-p1" onclick="selectPlant(0)">
                        <span class="fs-4">🌻</span><br><small class="text-dark fw-bold">Pea (50)</small>
                    </button>
                    <button class="btn btn-game-select py-2" id="btn-p2" onclick="selectPlant(1)">
                        <span class="fs-4">🛡️</span><br><small class="text-dark fw-bold">Nut (25)</small>
                    </button>
                    <button class="btn btn-game-select py-2" id="btn-p3" onclick="selectPlant(2)">
                        <span class="fs-4">🔥</span><br><small class="text-dark fw-bold">Fire (100)</small>
                    </button>
                </div>
            </div>

            <div class="col-md-2">
                <div class="d-grid gap-2 h-100">
                    <button id="ctrlBtn" class="btn btn-success fw-bold shadow-sm" onclick="toggleGame()">START</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">RESET</button>
                </div>
            </div>
        </div>

        <div class="position-relative d-inline-block overflow-hidden rounded shadow border border-3 border-success mb-3">
            <div id="pauseOverlay" class="game-overlay">GAME PAUSED</div>
            <div class="overflow-auto" style="max-width: 100%;">
                <div style="min-width: 1000px; line-height: 0;">
                    <canvas id="pvzCanvas" width="1000" height="500" style="cursor: pointer; display: block;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    const canvas = document.getElementById('pvzCanvas');
    const ctx = canvas.getContext('2d');
    const sunEl = document.getElementById('sunCount');
    const scoreEl = document.getElementById('gameScore');
    const ctrlBtn = document.getElementById('ctrlBtn');
    const statusBadge = document.getElementById('gameStatus');
    const pauseOverlay = document.getElementById('pauseOverlay');

    const cellSize = 100;
    const rows = 5;
    const cols = 10;
    let sun = 250;
    let score = 0;
    let gameActive = false; // Initially false
    let selectedPlantType = 0;
    let frameCount = 0; 

    const plants = [];
    const enemies = [];
    const projectiles = [];

    const plantTypes = [
        { icon: "🌻", cost: 50, health: 120, fireRate: 90 },
        { icon: "🛡️", cost: 25, health: 800, fireRate: 0 },
        { icon: "🔥", cost: 100, health: 120, fireRate: 45 }
    ];

    function toggleGame() {
        gameActive = !gameActive;
        if (gameActive) {
            ctrlBtn.innerText = "STOP";
            ctrlBtn.classList.replace('btn-success', 'btn-danger');
            statusBadge.innerText = "Running";
            statusBadge.classList.replace('bg-secondary', 'bg-success');
            pauseOverlay.style.display = "none";
            animate(); // Restart animation loop
        } else {
            ctrlBtn.innerText = "START";
            ctrlBtn.classList.replace('btn-danger', 'btn-success');
            statusBadge.innerText = "Paused";
            statusBadge.classList.replace('bg-success', 'bg-secondary');
            pauseOverlay.style.display = "flex";
        }
    }

    function selectPlant(idx) {
        selectedPlantType = idx;
        document.querySelectorAll('.btn-game-select').forEach((btn, i) => {
            btn.classList.toggle('active', i === idx);
        });
    }

    class Plant {
        constructor(x, y, typeIdx) {
            this.x = x + 25;
            this.y = y + 25;
            this.config = plantTypes[typeIdx];
            this.health = this.config.health;
            this.timer = 0;
        }
        update() {
            if (this.config.fireRate > 0) {
                if (this.timer % this.config.fireRate === 0) {
                    projectiles.push({x: this.x + 40, y: this.y + 20, speed: 7});
                }
                this.timer++;
            }
        }
        draw() {
            ctx.font = "40px Arial";
            ctx.fillText(this.config.icon, this.x, this.y + 35);
            if(this.health < this.config.health) {
                ctx.fillStyle = "red";
                ctx.fillRect(this.x, this.y + 45, 50, 5);
                ctx.fillStyle = "#00ff00";
                ctx.fillRect(this.x, this.y + 45, (this.health/this.config.health)*50, 5);
            }
        }
    }

    class Enemy {
        constructor(y) {
            this.x = canvas.width;
            this.y = y + 25;
            if (score < 170) {
                this.icon = "🐛"; this.maxHealth = 100; this.speed = 0.7; this.dmg = 0.8; this.color = "#28a745";
            } else if (score < 500) {
                this.icon = "🪲"; this.maxHealth = 200; this.speed = 1.0; this.dmg = 1.2; this.color = "#fd7e14";
            } else {
                this.icon = "🦂"; this.maxHealth = 400; this.speed = 1.4; this.dmg = 2.5; this.color = "#6f42c1";
            }
            this.speed += Math.random() * 0.3;
            this.health = this.maxHealth;
        }
        update() {
            let blocked = false;
            plants.forEach(p => {
                if (this.x < p.x + 40 && this.x > p.x && this.y > p.y - 10 && this.y < p.y + 50) {
                    blocked = true;
                    p.health -= this.dmg;
                }
            });
            if (!blocked) this.x -= this.speed;
        }
        draw() {
            ctx.font = "40px Arial";
            ctx.fillText(this.icon, this.x, this.y + 35);
            ctx.fillStyle = "rgba(0,0,0,0.5)";
            ctx.fillRect(this.x, this.y - 10, 45, 6);
            ctx.fillStyle = this.color;
            ctx.fillRect(this.x, this.y - 10, (this.health/this.maxHealth)*45, 6);
        }
    }

    canvas.addEventListener('mousedown', (e) => {
        if (!gameActive) return;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const mouseX = (e.clientX - rect.left) * scaleX;
        const mouseY = (e.clientY - rect.top) * scaleY;
        const gridX = Math.floor(mouseX / cellSize) * cellSize;
        const gridY = Math.floor(mouseY / cellSize) * cellSize;
        if (plants.some(p => p.x === gridX + 25 && p.y === gridY + 25)) return;
        const pType = plantTypes[selectedPlantType];
        if (sun >= pType.cost) {
            plants.push(new Plant(gridX, gridY, selectedPlantType));
            sun -= pType.cost;
            sunEl.innerText = sun;
        }
    });

    function animate() {
        if (!gameActive) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        frameCount++;

        for(let r=0; r<rows; r++) {
            for(let c=0; c<cols; c++) {
                ctx.fillStyle = (r + c) % 2 === 0 ? "#2d5a27" : "#35662d";
                ctx.fillRect(c*cellSize, r*cellSize, cellSize, cellSize);
            }
        }

        plants.forEach((p, i) => {
            p.draw(); p.update();
            if (p.health <= 0) plants.splice(i, 1);
        });

        projectiles.forEach((p, i) => {
            ctx.fillStyle = "yellow";
            ctx.beginPath(); ctx.arc(p.x, p.y, 6, 0, Math.PI*2); ctx.fill();
            p.x += p.speed;
            if (p.x > canvas.width) projectiles.splice(i, 1);
        });

        let spawnRate = (score > 800) ? 50 : (score > 500) ? 80 : (score > 170) ? 140 : 200;
        if (frameCount % spawnRate === 0) {
            enemies.push(new Enemy(Math.floor(Math.random() * rows) * cellSize));
        }

        enemies.forEach((enemy, ei) => {
            enemy.draw(); enemy.update();
            if (enemy.x < 0) {
                gameActive = false;
                alert("Game Over! Score: " + score);
                location.reload();
            }
            projectiles.forEach((proj, pi) => {
                if (proj.x > enemy.x && proj.x < enemy.x + 40 && proj.y > enemy.y && proj.y < enemy.y + 50) {
                    enemy.health -= 25; 
                    projectiles.splice(pi, 1);
                    if (enemy.health <= 0) {
                        enemies.splice(ei, 1);
                        score += 10; sun += 25;
                        scoreEl.innerText = score; sunEl.innerText = sun;
                    }
                }
            });
        });

        if (score >= 1000) {
            gameActive = false;
            alert("🏆 CONGRATULATIONS MASTER GARDENER!");
            location.reload();
        }
        requestAnimationFrame(animate);
    }

    setInterval(() => { if(gameActive) { sun += 25; sunEl.innerText = sun; } }, 2500);
    
    // Initial draw to show grid
    gameActive = true; 
    animate(); 
    toggleGame(); // Start it in "Paused" state
    </script>
</section>

</main>

<?php include('footer.php'); ?>