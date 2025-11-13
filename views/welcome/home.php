<?php

/** views/welcome/home.php — rendu dans base.php */
?>
<?php
$title = "Good Book — Accueil";
?>

<main id="app" class="gb-main">
    <!-- HERO SECTION -->
    <section class="hero">
        <div class="gb-container hero-container">
            <div class="hero-content">
                <h1>Bienvenue sur <span>Good Book</span></h1>
                <p>Découvrez et lisez les meilleurs livres numériques en RDC.</p>
                <a href="<?= url('/explore') ?>" class="gb-btn-gold">Explorer les livres</a>
            </div>
            <div class="hero-image">
                <img src="<?= asset('assets/images/hero-books.jpeg') ?>" alt="Livres numériques">
            </div>
        </div>
    </section>

    <!-- DISCOVER SECTION -->
    <section class="discover">
        <div class="gb-container">
            <h2>Découvrir</h2>
            <div class="cards">
                <div class="card">
                    <img src="<?= asset('assets/images/book1.jpeg') ?>" alt="Livre 1">
                    <h3>Roman contemporain</h3>
                    <p>Explorez des histoires captivantes de nos auteurs locaux.</p>
                </div>
                <div class="card">
                    <img src="<?= asset('assets/images/book2.jpeg') ?>" alt="Livre 2">
                    <h3>Science & Technologie</h3>
                    <p>Plongez dans le monde de l’innovation et du savoir.</p>
                </div>
                <div class="card">
                    <img src="<?= asset('assets/images/book3.jpg') ?>" alt="Livre 3">
                    <h3>Développement personnel</h3>
                    <p>Inspirez-vous et améliorez vos compétences.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- POPULAR BOOKS SECTION -->
    <section class="popular">
        <div class="gb-container">
            <h2>Populaires</h2>
            <div class="cards">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="card">
                        <img src="<?= asset("assets/images/popular$i.jpg") ?>" alt="Livre populaire <?= $i ?>">
                        <h3>Livre populaire <?= $i ?></h3>
                        <p>Auteur <?= $i ?></p>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- AUTHORS SECTION -->
    <section class="authors">
        <div class="gb-container">
            <h2>Auteurs à découvrir</h2>
            <div class="cards authors-cards">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="author-card">
                        <img src="<?= asset("assets/images/author$i.jpg") ?>" alt="Auteur <?= $i ?>">
                        <h3>Auteur <?= $i ?></h3>
                        <p>Spécialité / Genre</p>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
</main>

<?php include base_path('views/partials/footer.php'); ?>