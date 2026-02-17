/**
 * Mailrelay Module JavaScript
 */

import './bootstrap';
import 'bootstrap';

// Import Chart.js for analytics
import Chart from 'chart.js/auto';

// Import Quill for rich text editing
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Make Chart and Quill globally available
window.Chart = Chart;
window.Quill = Quill;

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Mailrelay utility functions
window.Mailrelay = {
    /**
     * Initialize Quill editor
     */
    initQuillEditor: function(selector, options = {}) {
        const defaultOptions = {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            ...options
        };

        return new Quill(selector, defaultOptions);
    },

    /**
     * Insert variable into Quill editor
     */
    insertVariable: function(editor, variable) {
        const range = editor.getSelection(true);
        editor.insertText(range.index, variable);
        editor.setSelection(range.index + variable.length);
    },

    /**
     * Initialize campaign analytics chart
     */
    initCampaignChart: function(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Aperturas',
                    data: data.opens || [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4
                }, {
                    label: 'Clics',
                    data: data.clicks || [],
                    borderColor: 'rgb(153, 102, 255)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Initialize validation statistics chart
     */
    initValidationChart: function(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Válidos', 'Inválidos', 'Pendientes', 'Desechables'],
                datasets: [{
                    data: [
                        data.valid || 0,
                        data.invalid || 0,
                        data.pending || 0,
                        data.disposable || 0
                    ],
                    backgroundColor: [
                        'rgb(25, 135, 84)',
                        'rgb(220, 53, 69)',
                        'rgb(255, 193, 7)',
                        'rgb(253, 126, 20)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    },

    /**
     * Show loading spinner on button
     */
    showButtonLoading: function(button, text = 'Procesando...') {
        const originalText = button.innerHTML;
        button.dataset.originalText = originalText;
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;
    },

    /**
     * Hide loading spinner on button
     */
    hideButtonLoading: function(button) {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
};
