(function () {
    'use strict';

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || target.type !== 'radio') {
            return;
        }
        var field = target.closest('.maie-choice-field');
        if (!field) {
            return;
        }
        field.querySelectorAll('.maie-choice-card').forEach(function (card) {
            card.classList.remove('is-selected');
        });
        var selected = target.closest('.maie-choice-card');
        if (selected) {
            selected.classList.add('is-selected');
        }
    });

    var progressPanel = document.getElementById('maie-job-progress');
    if (!progressPanel || typeof window.maieAdmin === 'undefined') {
        return;
    }

    var jobId = parseInt(progressPanel.getAttribute('data-job-id') || '0', 10);
    if (!jobId) {
        return;
    }

    var bar = document.getElementById('maie-progress-bar');
    var percent = document.getElementById('maie-progress-percent');
    var status = document.getElementById('maie-progress-status');
    var step = document.getElementById('maie-progress-step');
    var message = document.getElementById('maie-progress-message');
    var timeline = document.getElementById('maie-job-timeline');
    var track = progressPanel.querySelector('.maie-progress-track');
    var pollTimer = null;
    var queuedPolls = 0;
    var kickAttempted = false;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderEvents(events) {
        if (!timeline) {
            return;
        }
        if (!Array.isArray(events) || events.length === 0) {
            timeline.innerHTML = '<li class="maie-timeline-item is-waiting"><span class="maie-timeline-title">בתור</span><small>המשימה נוספה לתור וממתינה להפעלה.</small></li>';
            return;
        }

        timeline.innerHTML = events.map(function (event) {
            var label = event.step_label || event.step || 'שלב';
            var eventMessage = event.message || 'השלב עודכן.';
            var eventProgress = parseInt(event.progress_percent || 0, 10);
            var created = event.created_at ? ' · ' + event.created_at : '';
            return '<li class="maie-timeline-item">' +
                '<span class="maie-timeline-bullet"></span>' +
                '<span class="maie-timeline-title">' + escapeHtml(label) + '</span>' +
                '<small>' + escapeHtml(eventMessage + ' · ' + eventProgress + '%' + created) + '</small>' +
                '</li>';
        }).join('');
    }

    function updatePanel(data) {
        var value = Math.max(0, Math.min(100, parseInt(data.progress_percent || 0, 10)));
        if (bar) {
            bar.style.width = value + '%';
        }
        if (percent) {
            percent.textContent = value + '%';
        }
        if (track) {
            track.setAttribute('aria-valuenow', String(value));
        }
        if (status) {
            status.textContent = data.status_label || data.status || '';
        }
        if (step) {
            step.textContent = data.step_label || data.step || '';
        }
        if (message) {
            message.textContent = data.message || 'המשימה מתעדכנת.';
        }
        renderEvents(data.events || []);
        if (data.status === 'completed' || data.status === 'failed' || data.status === 'skipped') {
            if (pollTimer) {
                window.clearTimeout(pollTimer);
                pollTimer = null;
            }
        }
    }

    function scheduleNext() {
        pollTimer = window.setTimeout(fetchStatus, parseInt(window.maieAdmin.pollMs || 1500, 10));
    }

    function kickJob() {
        if (kickAttempted) return;
        kickAttempted = true;
        var formData = new FormData();
        formData.append('action', 'maie_kick_job');
        formData.append('job_id', String(jobId));
        formData.append('nonce', window.maieAdmin.jobStatusNonce || '');
        window.fetch(window.maieAdmin.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        }).catch(function () {});
    }

    function fetchStatus() {
        var url = new URL(window.maieAdmin.ajaxUrl, window.location.origin);
        url.searchParams.set('action', 'maie_job_status');
        url.searchParams.set('job_id', String(jobId));
        url.searchParams.set('nonce', window.maieAdmin.jobStatusNonce || '');
        window.fetch(url.toString(), { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.success || !payload.data) {
                    scheduleNext();
                    return;
                }
                // If the job is still queued after 3 polls (~4.5 s), nudge WP-Cron
                if (payload.data.status === 'queued') {
                    queuedPolls++;
                    if (queuedPolls >= 3) {
                        kickJob();
                    }
                } else {
                    queuedPolls = 0;
                }
                updatePanel(payload.data);
                if (payload.data.status !== 'completed' && payload.data.status !== 'failed' && payload.data.status !== 'skipped') {
                    scheduleNext();
                }
            })
            .catch(function () {
                scheduleNext();
            });
    }

    scheduleNext();
})();
