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
            const response = await fetch('index.php?action=list');
            const data = await response.json();
            this.images = data;
            this.renderCarousel();
            this.renderGrid();
        } catch (error) {
            console.error('Error loading images:', error);
        }
    }

    renderCarousel() {
        const track = document.getElementById('carousel-track');
        const dotsContainer = document.getElementById('carousel-dots');

        if (!track || !dotsContainer) return;

        if (this.images.length === 0) {
            track.innerHTML = '<div class="empty-state"><p>No hay imágenes. Sube una para comenzar.</p></div>';
            dotsContainer.innerHTML = '';
            return;
        }

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
        if (!grid) return;

        if (this.images.length === 0) {
            grid.innerHTML = '<div class="empty-state"><p>No hay imágenes en la galería.</p></div>';
            return;
        }

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
            const response = await fetch('index.php?action=status');
            const data = await response.json();
            this.debugMode = data.debug_mode;

            const toggle = document.getElementById('debug-toggle');
            if (toggle) toggle.checked = this.debugMode;

            this.updateDebugPanel(data);
        } catch (error) {
            console.error('Error loading debug state:', error);
        }
    }

    async toggleDebug(enabled) {
        try {
            await fetch('index.php?action=toggleDebug', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled })
            });

            this.debugMode = enabled;
            this.updateDebugVisibility();
        } catch (error) {
            console.error('Error toggling debug:', error);
        }
    }

    updateDebugVisibility() {
        const panel = document.getElementById('debug-panel');
        if (panel) {
            panel.classList.toggle('visible', this.debugMode);
        }
    }

    updateDebugPanel(data) {
        const panel = document.getElementById('debug-panel');
        if (!panel) return;

        panel.innerHTML = `
            <h4>Debug Panel</h4>
            <div class="debug-indicators">
                <div class="debug-item">
                    <span class="debug-dot ${data.db_connected ? 'green' : 'red'}"></span>
                    <span>DB: ${data.db_connected ? 'Connected' : 'Disconnected'}</span>
                    ${data.db_error ? `<span style="font-size:0.75rem;color:var(--cafe-300);">${data.db_error}</span>` : ''}
                </div>
                <div class="debug-item">
                    <span class="debug-dot ${data.s3_configured ? 'green' : 'yellow'}"></span>
                    <span>S3: ${data.s3_configured ? 'Configured' : 'Not configured'}</span>
                </div>
                <div class="debug-item">
                    <span class="debug-dot ${data.debug_mode ? 'green' : 'gray'}"></span>
                    <span>Debug: ${data.debug_mode ? 'ON' : 'OFF'}</span>
                </div>
                <div class="debug-item">
                    <span>Memory: ${data.memory_usage} KB</span>
                </div>
                <div class="debug-item">
                    <span>PHP: ${data.php_version}</span>
                </div>
            </div>
        `;

        this.updateDebugVisibility();
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

        try {
            const response = await fetch('index.php?action=upload', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Imagen subida exitosamente', 'success');
                form.reset();
                await this.loadImages();
            } else {
                this.showMessage(result.error || 'Error al subir imagen', 'error');
            }
        } catch (error) {
            this.showMessage('Error de conexión', 'error');
        }
    }

    openEditModal(id) {
        const image = this.images.find(img => img.id === id);
        if (!image) return;

        document.getElementById('edit-id').value = image.id;
        document.getElementById('edit-title').value = image.title;
        document.getElementById('edit-description').value = image.description || '';

        document.getElementById('edit-modal').classList.add('active');
    }

    closeModal() {
        document.getElementById('edit-modal').classList.remove('active');
    }

    async handleEdit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);

        try {
            const response = await fetch('index.php?action=update', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Imagen actualizada', 'success');
                this.closeModal();
                await this.loadImages();
            } else {
                this.showMessage('Error al actualizar', 'error');
            }
        } catch (error) {
            this.showMessage('Error de conexión', 'error');
        }
    }

    async deleteImage(id) {
        if (!confirm('¿Estás seguro de eliminar esta imagen?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('index.php?action=delete', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Imagen eliminada', 'success');
                await this.loadImages();
            } else {
                this.showMessage('Error al eliminar', 'error');
            }
        } catch (error) {
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