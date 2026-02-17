<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-logo">
            <span class="logo-bracket">[</span>
            WIKI<span class="logo-accent">GAMES</span>
            <span class="logo-bracket">]</span>
        </div>
        <p class="footer-text">Base de données collaborative de jeux vidéo.</p>
        <div class="footer-line"></div>
        <p class="footer-copy">© <?= date('Y') ?> WikiGames — Tous droits réservés</p>
    </div>
</footer>

<script>
function toggleMenu() {
    document.querySelector('.main-nav').classList.toggle('open');
    document.querySelector('.hamburger').classList.toggle('active');
}

// Auto-dismiss flash after 4s
const flash = document.getElementById('flash-msg');
if (flash) setTimeout(() => flash.style.opacity = '0', 4000);
</script>
</body>
</html>
