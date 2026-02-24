<nav class="navbar-main" id="navbar">
    <div class="navbar-container">
        <button class="btn1" id="sidebarToggle">
            <img src="./assets/images/Icons/menu.svg" alt="" class=" icon16">
        </button>
        <div class="navbar-content">
            <div class="navbar-title">
                GoRide<span class="logo-dot">.</span>
            </div>

            <div class="navbar-actions">
                <button class="btn1">
                    <img src="./assets/images/Icons/clock.svg" alt="" class=" icon16">
                </button>
                <!-- <button class="btn1">
                    <img src="./assets/images/Icons/gift.svg" alt="" class=" icon16">
                </button> -->
                <a href="profile.php" class="avatar-link">
                    <button class="avatar-btn icon40">
                        <?= htmlspecialchars($initials) ?>
                    </button>
                </a>
            </div>
        </div>
    </div>
</nav>
