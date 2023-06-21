# Wiki-Games

## Description

Wiki Games est une plateforme Web conçue pour répertorier différents jeux et fournir des informations pertinentes à leur sujet. Ce projet vous permet d'ajouter, de modifier et de supprimer des jeux de la base de données. Chaque jeu comprend une image, un nom, un prix et un lien vers le jeu.

L'interface utilisateur présente les jeux dans une grille de cartes, chacune affichant l'image du jeu, son nom, son prix et les liens pour en savoir plus, le modifier ou le supprimer. Si le prix du jeu est de 0, le texte "Gratuit" s'affiche à la place du prix.

## Fonctionnalités

Affichage des jeux : Les jeux sont affichés dans une grille. Pour chaque jeu, une image, un nom, un prix et un lien vers le jeu sont affichés.
Ajout de jeux : Un formulaire est disponible pour ajouter un nouveau jeu à la base de données. Le formulaire nécessite un nom de jeu, une image, un prix et un lien vers le jeu.
Modification des jeux : Chaque jeu dispose d'un lien "Modifier" qui renvoie à un formulaire de modification. Le formulaire est pré-rempli avec les détails actuels du jeu.
Suppression de jeux : Chaque jeu dispose d'un lien "Supprimer". Un clic sur ce lien déclenche une fenêtre modale demandant à l'utilisateur de confirmer la suppression.
Pagination : La liste des jeux est paginée, avec une limite de 12 jeux par page.

## Comment utiliser

Clonez le dépôt sur votre machine locale.
Installez un serveur Apache et MySQL (vous pouvez utiliser XAMPP, WAMP, MAMP ou tout autre outil similaire).
Importez la base de données games.sql dans votre MySQL local.
Ouvrez le projet dans un navigateur web en naviguant vers localhost/path_to_your_project.

## Technologies utilisées
* PHP
* MySQL
* HTML
* CSS
* JavaScript
* SweetAlert2
* Swiper.js

## Auteur
Divhthoth
