/**
 * HelpdeskCampaigns widget — embeds on customer-facing pages.
 *
 * Usage:
 *   <script src="/modules/helpdeskcampaigns/js/widget.js"
 *           data-base-url="https://app.example.com"
 *           data-campaign-id="42"
 *           data-customer-id="123"
 *           data-show-after-seconds="3"></script>
 *
 * Lifecycle:
 *   1. On script load, wait `show-after-seconds` (default 0).
 *   2. POST /helpdesk/campaigns/track/view → server returns { variant, ... }
 *      or refuses (404 / TARGETING_MISMATCH / FREQUENCY_CAPPED).
 *   3. If accepted, render the campaign content from the returned variant
 *      (or default content) as a popup/banner/slide-in.
 *   4. On user click of CTA: POST /track/click/{impression_id}
 *   5. On dismiss: POST /track/click with metadata.dismissed=true (optional).
 *
 * Visitor identity is best-effort:
 *   - customer_id from data-attr (logged-in user)
 *   - customer_session_id from cookie (anonymous session)
 *   - falls back to IP on the server
 */
(function () {
  'use strict';

  if (window.HelpdeskCampaigns) return;
  window.HelpdeskCampaigns = {};

  const script = document.currentScript;
  if (!script) return;

  const config = {
    baseUrl: script.dataset.baseUrl || '',
    campaignId: parseInt(script.dataset.campaignId, 10),
    customerId: script.dataset.customerId ? parseInt(script.dataset.customerId, 10) : null,
    showAfterSeconds: parseInt(script.dataset.showAfterSeconds || '0', 10),
    autoStart: script.dataset.autoStart !== 'false',
  };

  if (!config.campaignId) return;

  function getSessionId() {
    const match = document.cookie.match(/(?:^|;\s*)hd_session_id=([^;]+)/);
    if (match) return decodeURIComponent(match[1]);
    const id = 'sess-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
    document.cookie = `hd_session_id=${encodeURIComponent(id)}; path=/; max-age=2592000; SameSite=Lax`;
    return id;
  }

  function detectDevice() {
    if (/Mobi|Android|iPhone/i.test(navigator.userAgent)) return 'mobile';
    if (/iPad|Tablet/i.test(navigator.userAgent)) return 'tablet';
    return 'desktop';
  }

  async function trackView() {
    const payload = {
      campaign_id: config.campaignId,
      customer_id: config.customerId,
      customer_session_id: getSessionId(),
      page_url: location.href,
      device_type: detectDevice(),
      browser: navigator.userAgent.substring(0, 100),
      language: navigator.language || '',
    };

    try {
      const res = await fetch(`${config.baseUrl}/helpdesk/campaigns/track/view`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      return data.success ? data : null;
    } catch (e) {
      console.warn('[HelpdeskCampaigns] track/view failed', e);
      return null;
    }
  }

  async function trackClick(impressionId) {
    if (!impressionId) return;
    try {
      await fetch(`${config.baseUrl}/helpdesk/campaigns/track/click/${impressionId}`, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        keepalive: true, // survives navigation
      });
    } catch (e) {
      // best-effort
    }
  }

  function renderPopup(variant) {
    const content = variant?.content || {};
    const appearance = variant?.appearance || {};

    const overlay = document.createElement('div');
    overlay.id = 'hd-campaign-overlay';
    overlay.style.cssText = `
      position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;
      display:flex;align-items:center;justify-content:center;
    `;

    const box = document.createElement('div');
    box.style.cssText = `
      background:${appearance.bg || '#fff'};color:${appearance.color || '#222'};
      padding:32px 28px;border-radius:12px;max-width:480px;width:90%;
      box-shadow:0 20px 50px rgba(0,0,0,.3);position:relative;
      font-family:system-ui,-apple-system,sans-serif;
    `;

    box.innerHTML = `
      <button id="hd-campaign-close" style="position:absolute;top:8px;right:12px;background:none;border:0;font-size:28px;cursor:pointer;color:#999;">&times;</button>
      ${content.headline ? `<h3 style="margin:0 0 12px;font-size:20px;">${escapeHtml(content.headline)}</h3>` : ''}
      ${content.body ? `<p style="margin:0 0 20px;line-height:1.5;">${escapeHtml(content.body)}</p>` : ''}
      ${content.cta_label ? `
        <a href="${content.cta_url || '#'}" id="hd-campaign-cta"
           style="display:inline-block;padding:10px 20px;background:${appearance.accent || '#90bb13'};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">
          ${escapeHtml(content.cta_label)}
        </a>` : ''}
    `;
    overlay.appendChild(box);
    return overlay;
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
  }

  async function start() {
    if (config.showAfterSeconds > 0) {
      await new Promise(r => setTimeout(r, config.showAfterSeconds * 1000));
    }

    const result = await trackView();
    if (!result) return;

    const node = renderPopup(result.variant);
    document.body.appendChild(node);

    const close = () => node.remove();
    node.querySelector('#hd-campaign-close')?.addEventListener('click', close);
    node.addEventListener('click', e => { if (e.target === node) close(); });

    const cta = node.querySelector('#hd-campaign-cta');
    if (cta) {
      cta.addEventListener('click', () => {
        trackClick(result.impression_id);
      });
    }
  }

  window.HelpdeskCampaigns.start = start;
  window.HelpdeskCampaigns.trackView = trackView;
  window.HelpdeskCampaigns.trackClick = trackClick;

  if (config.autoStart) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start);
    } else {
      start();
    }
  }
})();
