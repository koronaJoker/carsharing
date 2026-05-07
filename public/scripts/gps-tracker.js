/**
 * GPS Location Tracker
 * Sends device GPS location to server every minute during active rental
 */
class GpsLocationTracker {
    constructor(rentalId, appBaseUrl = '') {
        this.rentalId = rentalId;
        this.appBaseUrl = appBaseUrl.replace(/\/$/, '');
        this.trackingInterval = null;
        this.watchId = null;
        this.lastLocation = null;
        this.isTracking = false;
    }

    /**
     * Start GPS tracking and sending location every minute
     */
    start() {
        if (this.isTracking) {
            console.warn('GPS tracking already started');
            return;
        }

        if (!navigator.geolocation) {
            console.error('Geolocation not supported by this browser');
            this.showError('GPS is not supported by your browser');
            return;
        }

        this.isTracking = true;
        console.log('GPS tracking started for rental', this.rentalId);

        // Get location immediately
        this.updateLocation();

        // Then every 60 seconds
        this.trackingInterval = setInterval(() => {
            this.updateLocation();
        }, 60000); // 60 seconds = 1 minute
    }

    /**
     * Stop GPS tracking
     */
    stop() {
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
            this.trackingInterval = null;
        }

        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        this.isTracking = false;
        console.log('GPS tracking stopped');
    }

    /**
     * Request current location and send to server
     */
    updateLocation() {
        if (!this.isTracking) {
            return;
        }

        console.log('Fetching GPS location...');

        // Use getCurrentPosition instead of watchPosition for better control
        navigator.geolocation.getCurrentPosition(
            (position) => this.sendLocation(position),
            (error) => this.handleError(error),
            {
                enableHighAccuracy: true,  // Use GPS instead of WiFi/cellular
                timeout: 10000,             // 10 second timeout
                maximumAge: 0               // Don't use cached position
            }
        );
    }

    /**
     * Send location to server API
     */
    sendLocation(position) {
        const coords = position.coords;

        const data = {
            rental_id: this.rentalId,
            latitude: coords.latitude,
            longitude: coords.longitude,
            speed: coords.speed || 0,
            accuracy: Math.round(coords.accuracy) || 10
        };

        console.log('Sending location:', data);

        fetch(this.endpoint('save'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Location saved:', data);
                    this.lastLocation = {
                        lat: coords.latitude,
                        lng: coords.longitude,
                        timestamp: new Date()
                    };
                    this.showSuccess(`Location updated at ${new Date().toLocaleTimeString()}`);
                } else {
                    console.error('Server error:', data.error);
                    this.showError('Failed to save location: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                this.showError('Network error: ' + error.message);
            });
    }

    endpoint(action, params = {}) {
        const url = new URL(`${this.appBaseUrl}/`, window.location.origin);
        url.searchParams.set('page', `api/location/${action}`);

        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.set(key, value);
        });

        return url.toString();
    }

    /**
     * Handle geolocation errors
     */
    handleError(error) {
        let message = '';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'GPS permission denied. Please enable location access.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'GPS position unavailable. Try again later.';
                break;
            case error.TIMEOUT:
                message = 'GPS request timeout. Check your connection.';
                break;
            default:
                message = 'GPS error: ' + error.message;
        }
        console.error(message);
        this.showError(message);
    }

    /**
     * Show success notification
     */
    showSuccess(message) {
        console.log('[SUCCESS]', message);
        // You can integrate with your notification system here
        if (window.showNotification) {
            window.showNotification(message, 'success');
        }
    }

    /**
     * Show error notification
     */
    showError(message) {
        console.error('[ERROR]', message);
        // You can integrate with your notification system here
        if (window.showNotification) {
            window.showNotification(message, 'error');
        }
    }

    /**
     * Get last recorded location
     */
    getLastLocation() {
        return this.lastLocation;
    }

    /**
     * Check if currently tracking
     */
    isTrackingActive() {
        return this.isTracking;
    }

    /**
     * Get route history from server
     */
    async getRoute(minutes = 60) {
        try {
            const response = await fetch(
                this.endpoint('route', {rental_id: this.rentalId, minutes})
            );
            const data = await response.json();

            if (data.success) {
                console.log(`Route: ${data.points_count} points, ${data.distance_km} km`);
                return data;
            } else {
                console.error('Failed to get route:', data.error);
                return null;
            }
        } catch (error) {
            console.error('Error fetching route:', error);
            return null;
        }
    }

    /**
     * Get distance traveled
     */
    async getDistance(minutes = 60) {
        try {
            const response = await fetch(
                this.endpoint('distance', {rental_id: this.rentalId, minutes})
            );
            const data = await response.json();

            if (data.success) {
                console.log(`Distance: ${data.distance_km} km in ${minutes} minutes`);
                return data.distance_km;
            } else {
                console.error('Failed to get distance:', data.error);
                return 0;
            }
        } catch (error) {
            console.error('Error fetching distance:', error);
            return 0;
        }
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GpsLocationTracker;
}

(function initGpsTrackerFromScriptTag() {
    const script = document.currentScript;

    if (!script || !script.hasAttribute('data-gps-tracker')) {
        return;
    }

    const rentalId = Number(script.dataset.rentalId);

    if (!rentalId) {
        console.error('GPS tracking cannot start without a rental ID');
        return;
    }

    window.activeGpsTracker = new GpsLocationTracker(rentalId, script.dataset.baseUrl || '');
    window.activeGpsTracker.start();
})();
