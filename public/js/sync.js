class SyncManager {
    constructor() {
        this.STORAGE_KEY = 'trakfin_data';
        // Utiliser l'URL de l'API définie globalement ou fallback relatif
        this.API_URL = window.APP_API_URL || 'api/sync';
        this.isSyncing = false;

        this.init();
    }

    init() {
        // Attendre que la page soit totalement chargée pour éviter les conflits d'icônes
        window.addEventListener('load', () => {
            if (navigator.onLine) {
                this.sync();
            }
        });

        // Écouter le retour de la connexion
        window.addEventListener('online', () => {
            console.log('Connexion rétablie, synchronisation...');
            showToast('Connexion rétablie', 'info');
            this.sync();
        });

        window.addEventListener('offline', () => {
            showToast('Mode hors-ligne activé', 'info');
        });
    }

    async sync() {
        if (this.isSyncing) return;
        this.isSyncing = true;

        // On récupère l'élément
        let btn = document.getElementById('btn-sync');
        if (btn) btn.classList.add('animate-spin');

        try {
            const response = await fetch(this.API_URL);
            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();

            // Sauvegarder dans le stockage local
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
            localStorage.setItem('trakfin_last_sync', new Date().toISOString());

            console.log('Synchronisation terminée', data);

        } catch (error) {
            console.error('Erreur de synchronisation:', error);
        } finally {
            this.isSyncing = false;
            // Récupérer à nouveau l'élément car il a pu être remplacé par Lucide (<i> -> <svg>)
            btn = document.getElementById('btn-sync');
            if (btn) btn.classList.remove('animate-spin');
        }
    }

    getData() {
        const data = localStorage.getItem(this.STORAGE_KEY);
        return data ? JSON.parse(data) : null;
    }
}

// Initialiser
const syncManager = new SyncManager();

// Exposer globalement pour le bouton
window.forceSync = () => {
    showToast('Synchronisation en cours...', 'info');
    syncManager.sync().then(() => {
        showToast('Données à jour', 'success');
    });
};
