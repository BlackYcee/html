class Gallery {
    constructor() {
        this.images = [];
        this.currentIndex = 0;
        this.debugMode = false;
    }

    async init() {
        await this.loadImages();
        this.setupEventListeners();
        await this.loadDebugState();
    }

    async loadImages() {
        try {
            DebugConsole.info('[REQUEST] GET index.php?action=list');
            const startTime = performance.now();
            const response = await fetch('index.php?action=list');
            const elapsed = (performance.now() - startTime).toFixed(2);
            const data = await response.json();
            this.images = data;
            DebugConsole.success(`[RESPONSE] action=list | Status: ${response.status} | Time: ${elapsed}ms | Images: ${data.length}`);
            this.renderCarousel();
            this.renderGrid();
        } catch (error) {
            DebugConsole.error(`[ERROR] loadImages: ${error.message}`);
            console.error('Error loading images:', error);
        }
    }

    renderCarousel() {
        const track = document.getElementById('carousel-track');
        const dotsContainer = document.getElementById('carousel-dots');

        if (!track || !dotsContainer) {
            DebugConsole.warn('[UI] Elementos del carrusel no encontrados');
            return;
        }

        if (this.images.length === 0) {
            track.innerHTML = '<div class="empty-state"><p>No hay imágenes. Sube una para comenzar.</p></div>';
            dotsContainer.innerHTML = '';
            DebugConsole.warn('[UI] Carrusel vacio - no hay imagenes');
            return;
        }

        DebugConsole.info(`[UI] Renderizando carrusel con ${this.images.length} imagen(es)`);
        track.innerHTML = this.images.map((img, i) => `
            <div class="carousel-slide" data-index="${i}">
                <img src="${img.s3_url || img.file_path}" alt="${img.title}" loading="${i === 0 ? 'eager' : 'lazy'}">
                <div class="slide-info">
                    <h3>${img.title}</h3>
                    ${img.description ? `<p>${img.description}</p>` : ''}
                </div>
            </div>
        `).join('');

        dotsContainer.innerHTML = this.images.map((_, i) => `
            <button class="carousel-dot ${i === 0 ? 'active' : ''}" data-index="${i}"></button>
        `).join('');

        this.updateCarouselButtons();
    }

    renderGrid() {
        const grid = document.getElementById('images-grid');
        if (!grid) {
            DebugConsole.warn('[UI] Grid de imagenes no encontrado');
            return;
        }

        if (this.images.length === 0) {
            grid.innerHTML = '<div class="empty-state"><p>No hay imágenes en la galería.</p></div>';
            DebugConsole.warn('[UI] Grid vacio - no hay imagenes');
            return;
        }

        DebugConsole.info(`[UI] Renderizando grid con ${this.images.length} imagen(es)`);
        grid.innerHTML = this.images.map(img => `
            <div class="image-card" data-id="${img.id}">
                <img src="${img.s3_url || img.file_path}" alt="${img.title}" loading="lazy">
                <div class="card-body">
                    <h4>${img.title}</h4>
                    <p>${img.description || 'Sin descripción'}</p>
                    <div class="card-actions">
                        <button class="btn btn-primary btn-small" onclick="gallery.openEditModal(${img.id})">Editar</button>
                        <button class="btn btn-danger btn-small" onclick="gallery.deleteImage(${img.id})">Eliminar</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    setupEventListeners() {
        document.getElementById('prev-btn')?.addEventListener('click', () => this.prevSlide());
        document.getElementById('next-btn')?.addEventListener('click', () => this.nextSlide());

        document.getElementById('carousel-dots')?.addEventListener('click', (e) => {
            if (e.target.classList.contains('carousel-dot')) {
                this.goToSlide(parseInt(e.target.dataset.index));
            }
        });

        document.getElementById('upload-form')?.addEventListener('submit', (e) => this.handleUpload(e));
        document.getElementById('debug-toggle')?.addEventListener('change', (e) => this.toggleDebug(e.target.checked));

        document.getElementById('close-modal')?.addEventListener('click', () => this.closeModal());
        document.getElementById('edit-form')?.addEventListener('submit', (e) => this.handleEdit(e));
    }

    async loadDebugState() {
        try {
            DebugConsole.info('[REQUEST] GET index.php?action=status');
            const response = await fetch('index.php?action=status');
            const data = await response.json();
            this.debugMode = data.debug_mode;

            DebugConsole.info(`[DEBUG] Mode: ${this.debugMode ? 'ON' : 'OFF'}`);
            DebugConsole.info(`[DB] Connected: ${data.db_connected}`);
            if (data.db_error) DebugConsole.error(`[DB] Error: ${data.db_error}`);
            DebugConsole.info(`[S3] Configured: ${data.s3_configured}`);
            DebugConsole.info(`[MEMORY] Usage: ${data.memory_usage} KB`);
            DebugConsole.info(`[PHP] Version: ${data.php_version}`);

            const toggle = document.getElementById('debug-toggle');
            if (toggle) toggle.checked = this.debugMode;

            this.updateDebugVisibility();
        } catch (error) {
            DebugConsole.error(`[ERROR] loadDebugState: ${error.message}`);
        }
    }

    async toggleDebug(enabled) {
        try {
            DebugConsole.info(`[REQUEST] POST index.php?action=toggleDebug | Body: ${JSON.stringify({ enabled })}`);
            const response = await fetch('index.php?action=toggleDebug', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled })
            });
            const result = await response.json();

            this.debugMode = enabled;
            DebugConsole.success(`[RESPONSE] toggleDebug | Debug: ${enabled ? 'ON' : 'OFF'}`);
            this.updateDebugVisibility();
        } catch (error) {
            DebugConsole.error(`[ERROR] toggleDebug: ${error.message}`);
        }
    }

    updateDebugVisibility() {
        const panel = document.getElementById('console-panel');
        if (panel) {
            if (this.debugMode) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        }
    }

    prevSlide() {
        if (this.images.length === 0) return;
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.updateCarousel();
    }

    nextSlide() {
        if (this.images.length === 0) return;
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.updateCarousel();
    }

    goToSlide(index) {
        this.currentIndex = index;
        this.updateCarousel();
    }

    updateCarousel() {
        const track = document.getElementById('carousel-track');
        const dots = document.querySelectorAll('.carousel-dot');

        if (track) {
            track.style.transform = `translateX(-${this.currentIndex * 100}%)`;
        }

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentIndex);
        });

        this.updateCarouselButtons();
    }

    updateCarouselButtons() {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');

        if (prevBtn) {
            prevBtn.style.display = this.images.length <= 1 ? 'none' : 'block';
        }
        if (nextBtn) {
            nextBtn.style.display = this.images.length <= 1 ? 'none' : 'block';
        }
    }

    async handleUpload(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const fileInput = form.querySelector('input[type="file"]');
        const fileName = fileInput?.files[0]?.name || 'unknown';

        DebugConsole.info(`[UPLOAD] Iniciando - Archivo: ${fileName}`);
        DebugConsole.info(`[UPLOAD] Titulo: ${form.title?.value || 'N/A'}`);

        try {
            DebugConsole.info('[REQUEST] POST index.php?action=upload');
            const startTime = performance.now();
            const response = await fetch('index.php?action=upload', {
                method: 'POST',
                body: formData
            });
            const elapsed = (performance.now() - startTime).toFixed(2);
            const result = await response.json();

            if (result.success) {
                DebugConsole.success(`[UPLOAD] Exito - Time: ${elapsed}ms | ID: ${result.data?.id}`);
                DebugConsole.info(`[UPLOAD] File path: ${result.data?.file_path}`);
                DebugConsole.info(`[UPLOAD] S3 URL: ${result.data?.s3_url || 'Local'}`);
                this.showMessage('Imagen subida exitosamente', 'success');
                form.reset();
                await this.loadImages();
            } else {
                DebugConsole.error(`[UPLOAD] Fallo - ${result.error}`);
                this.showMessage(result.error || 'Error al subir imagen', 'error');
            }
        } catch (error) {
            DebugConsole.error(`[UPLOAD] Error de conexion: ${error.message}`);
            this.showMessage('Error de conexión', 'error');
        }
    }

    openEditModal(id) {
        const image = this.images.find(img => img.id === id);
        if (!image) {
            DebugConsole.warn(`[EDIT] Imagen ID ${id} no encontrada`);
            return;
        }

        DebugConsole.info(`[EDIT] Abriendo modal para imagen ID: ${id}`);
        document.getElementById('edit-id').value = image.id;
        document.getElementById('edit-title').value = image.title;
        document.getElementById('edit-description').value = image.description || '';

        document.getElementById('edit-modal').classList.add('active');
    }

    closeModal() {
        DebugConsole.info('[EDIT] Cerrando modal');
        document.getElementById('edit-modal').classList.remove('active');
    }

    async handleEdit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const id = formData.get('id');
        const title = formData.get('title');

        DebugConsole.info(`[EDIT] Actualizando imagen ID: ${id} | Titulo: ${title}`);

        try {
            DebugConsole.info('[REQUEST] POST index.php?action=update');
            const response = await fetch('index.php?action=update', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                DebugConsole.success('[EDIT] Actualizacion exitosa');
                this.showMessage('Imagen actualizada', 'success');
                this.closeModal();
                await this.loadImages();
            } else {
                DebugConsole.error('[EDIT] Error en actualizacion');
                this.showMessage('Error al actualizar', 'error');
            }
        } catch (error) {
            DebugConsole.error(`[EDIT] Error de conexion: ${error.message}`);
            this.showMessage('Error de conexión', 'error');
        }
    }

    async deleteImage(id) {
        if (!confirm('¿Estás seguro de eliminar esta imagen?')) {
            DebugConsole.warn('[DELETE] Operacion cancelada por usuario');
            return;
        }

        DebugConsole.info(`[DELETE] Eliminando imagen ID: ${id}`);

        try {
            const formData = new FormData();
            formData.append('id', id);

            DebugConsole.info('[REQUEST] POST index.php?action=delete');
            const response = await fetch('index.php?action=delete', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                DebugConsole.success(`[DELETE] Imagen ID ${id} eliminada`);
                this.showMessage('Imagen eliminada', 'success');
                await this.loadImages();
            } else {
                DebugConsole.error('[DELETE] Error al eliminar');
                this.showMessage('Error al eliminar', 'error');
            }
        } catch (error) {
            DebugConsole.error(`[DELETE] Error de conexion: ${error.message}`);
            this.showMessage('Error de conexión', 'error');
        }
    }

    showMessage(text, type) {
        const msg = document.getElementById('message');
        if (!msg) return;

        msg.textContent = text;
        msg.className = `show ${type}`;

        setTimeout(() => {
            msg.classList.remove('show');
        }, 3000);
    }
}

const gallery = new Gallery();
document.addEventListener('DOMContentLoaded', () => gallery.init());