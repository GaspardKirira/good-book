   <?php include base_path('views/partials/nav.php'); ?>

   <?php /** views/partials/header.php */ ?>
   <header class="gb-header">
       <div class="gb-container">
           <div class="gb-logo">
               <a href="<?= url('/') ?>">
                   <img src="<?= asset('assets/logo/goodbook-gold.png') ?>" alt="Good Book" />
                   <span>Book</span>
               </a>
           </div>

           <nav class="gb-nav" id="main-nav">
               <a href="<?= url('/explore') ?>" class="<?= $active('/explore') ?>">Explorer</a>
               <a href="<?= url('/categories') ?>" class="<?= $active('/categories') ?>">Catégories</a>
               <a href="<?= url('/authors') ?>" class="<?= $active('/authors') ?>">Auteurs</a>
               <a href="<?= url('/library') ?>" class="<?= $active('/library') ?>">Ma bibliothèque</a>
           </nav>

           <div class="gb-actions">
               <form action="<?= url('/search') ?>" method="get" class="gb-search" role="search">
                   <input type="text" name="q" placeholder="Rechercher un livre..." aria-label="Rechercher" />
                   <button type="submit" aria-label="Chercher">🔍</button>
               </form>

               <a href="<?= url('/login') ?>" class="gb-btn-gold">Connexion</a>

               <!-- theme toggle -->
               <button id="theme-toggle" class="gb-theme-toggle" aria-label="Changer de thème">🌙</button>

               <!-- mobile menu toggle -->
               <button id="menu-toggle" class="gb-menu-toggle" aria-label="Ouvrir le menu">☰</button>
           </div>
       </div>
   </header>