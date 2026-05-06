<?php require_once __DIR__ . '/../templates/header.php'; ?>

<header>
    <h1>Galería de Imágenes</h1>
    <label class="debug-toggle">
        <input type="checkbox" id="debug-toggle">
        <span>Debug</span>
    </label>
</header>

<main>
    <div class="carousel-container">
        <div class="carousel-track" id="carousel-track"></div>
        <button class="carousel-btn prev" id="prev-btn">&#10094;</button>
        <button class="carousel-btn next" id="next-btn">&#10095;</button>
    </div>
    <div class="carousel-dots" id="carousel-dots"></div>

    <section class="management-section">
        <h2>Gestionar Imágenes</h2>

        <form class="upload-form" id="upload-form" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Título de la imagen" required>
            <textarea name="description" placeholder="Descripción (opcional)"></textarea>
            <input type="file" name="image" id="image-input" accept="image/*" required>
            <button type="submit" class="btn btn-primary">Subir Imagen</button>
        </form>

        <div class="images-grid" id="images-grid"></div>
    </section>
</main>

<div id="edit-modal" class="modal">
    <div class="modal-content">
        <h3>Editar Imagen</h3>
        <form id="edit-form">
            <input type="hidden" name="id" id="edit-id">
            <input type="text" name="title" id="edit-title" placeholder="Título" required>
            <textarea name="description" id="edit-description" placeholder="Descripción"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="close-modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="message"></div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

<footer>
    <div class="debug-panel" id="debug-panel"></div>
</footer>

<script src="/assets/js/main.js"></script>