const VERSION = "390";
const PREFIX = "softadastra";
const CACHE_STATIC = `${PREFIX}-static-v${VERSION}`;
const CACHE_PAGES = `${PREFIX}-pages-v${VERSION}`;
const CACHE_ASSETS = `${PREFIX}-assets-v${VERSION}`;
const CACHE_IMAGES = `${PREFIX}-images-v${VERSION}`;
const CACHE_API = `${PREFIX}-api-v${VERSION}`;

const OFFLINE_PAGE = "/offline.html";
const PLACEHOLDER_IMG = "/public/assets/images/placeholders/offline.png";

const PRECACHE_URLS = [
  "/",
  OFFLINE_PAGE,
  "/public/assets/favicon/favicon-32x32.png",
  "/public/assets/favicon/favicon-16x16.png",
  "/public/assets/favicon/android-chrome-192x192.png",
  "/public/assets/favicon/android-chrome-512x512.png",
];

/* --- Helpers --- */
const isSameOrigin = (url) => url.origin === self.location.origin;

const isHTMLNavigation = (request) =>
  request.mode === "navigate" ||
  (request.method === "GET" &&
    request.headers.get("accept")?.includes("text/html"));

const isAsset = (url) => /\.(?:css|js|mjs|map)$/.test(url.pathname);
const isImage = (url) =>
  /\.(?:png|jpg|jpeg|gif|webp|avif|svg)$/.test(url.pathname);
const isFont = (url) => /\.(?:woff2?|ttf|otf)$/.test(url.pathname);

const API_GET_ALLOWLIST = [
  "/api/products",
  "/api/categories",
  "/api/brands",
  "/api/search",
  "/api/orders/metrics",
];

const API_EXCLUDES = [
  "/api/login",
  "/api/logout",
  "/api/register",
  "/get-user",
];

function urlMatchesAny(pathname, list) {
  return list.some((p) => pathname === p || pathname.startsWith(p + "/"));
}

async function fetchWithTimeout(request, ms = 4000) {
  const ctrl = new AbortController();
  const id = setTimeout(() => ctrl.abort(), ms);
  try {
    return await fetch(request, { signal: ctrl.signal });
  } finally {
    clearTimeout(id);
  }
}

async function networkFirstForPage(event, timeoutMs = 8000) {
  const cache = await caches.open(CACHE_PAGES);
  const request = event.request;

  try {
    if (event.preloadResponse) {
      const preload = await event.preloadResponse;
      if (preload) {
        cache.put(request, preload.clone()).catch(() => {});
        return preload;
      }
    }
  } catch {}

  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const resp = await fetch(request, { signal: controller.signal });
    clearTimeout(id);
    if (resp && resp.ok) {
      cache.put(request, resp.clone()).catch(() => {});
    }
    return resp;
  } catch {
    clearTimeout(id);

    const cached = await cache.match(request);
    if (cached) return cached;

    const offline = await caches.match(OFFLINE_PAGE);
    if (offline) return offline;

    return new Response(
      "<!doctype html><meta charset='utf-8'><title>Offline</title><h1>Offline</h1><p>You appear to be offline.</p>",
      { headers: { "Content-Type": "text/html; charset=UTF-8" }, status: 200 }
    );
  }
}

async function staleWhileRevalidate(cacheName, request) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const fetchPromise = fetch(request)
    .then((resp) => {
      if (resp && (resp.ok || resp.type === "opaque"))
        cache.put(request, resp.clone()).catch(() => {});
      return resp;
    })
    .catch(() => null);
  return cached || fetchPromise || new Response("Offline", { status: 503 });
}

const IMG_MAX_ENTRIES = 120;
async function cacheFirstImages(request) {
  const cache = await caches.open(CACHE_IMAGES);
  const cached = await cache.match(request);
  if (cached) return cached;

  try {
    const resp = await fetch(request, { mode: "no-cors" });
    if (resp && (resp.ok || resp.type === "opaque")) {
      await cache.put(request, resp.clone());
      const keys = await cache.keys();
      if (keys.length > IMG_MAX_ENTRIES) {
        await cache.delete(keys[0]);
      }
      return resp;
    }
  } catch {}

  const fallback = await caches.match(PLACEHOLDER_IMG);
  return fallback || new Response("", { status: 404 });
}
self.addEventListener("install", (event) => {
  event.waitUntil(
    (async () => {
      try {
        const cacheStatic = await caches.open(CACHE_STATIC);
        await cacheStatic.addAll(
          PRECACHE_URLS.map((u) => new Request(u, { cache: "reload" }))
        );
      } finally {
        await self.skipWaiting();
      }
    })()
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    (async () => {
      const keep = new Set([
        CACHE_STATIC,
        CACHE_PAGES,
        CACHE_ASSETS,
        CACHE_IMAGES,
        CACHE_API,
      ]);
      const names = await caches.keys();
      await Promise.all(
        names.map((n) => (keep.has(n) ? Promise.resolve() : caches.delete(n)))
      );

      if ("navigationPreload" in self.registration) {
        try {
          await self.registration.navigationPreload.enable();
        } catch {}
      }

      await self.clients.claim();

      const clientsList = await self.clients.matchAll({
        includeUncontrolled: true,
        type: "window",
      });
      for (const client of clientsList)
        client.postMessage({ type: "CLEAR_ARTICLES_CACHE" });
    })()
  );
});

self.addEventListener("push", (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch {}

  const kind = data.kind || data.type;

  const ICONS = {
    message: {
      icon: "/icons/message.svg",
      badge: "/icons/badge-72.png",
    },
    notification: {
      icon: "/icons/bell.svg",
      badge: "/icons/badge-72.png",
    },
  };

  const isMsg = kind === "message";
  const iconSet = isMsg ? ICONS.message : ICONS.notification;

  let title = "Softadastra";
  let body = "New notification";
  let url = "/";

  if (isMsg) {
    title = data.title || "💬 New message";
    body = data.body || data.preview || "You have received a new message.";
    url = data.url || "/chat/home";
  } else {
    title = data.title || "🔔 Notification";
    body = data.body || "You have a new notification.";
    url = data.url || "/notifications";
  }

  const options = {
    body,
    icon: iconSet.icon,
    badge: iconSet.badge,
    tag: kind || "sa-general",
    renotify: true,
    data: { url },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const url = event.notification?.data?.url || "/";
  event.waitUntil(
    (async () => {
      const clientsArr = await self.clients.matchAll({
        type: "window",
        includeUncontrolled: true,
      });
      for (const c of clientsArr) {
        if (new URL(c.url).origin === self.location.origin) {
          await c.focus();
          return c.navigate(url);
        }
      }
      return self.clients.openWindow(url);
    })()
  );
});

self.addEventListener("message", (event) => {
  if (event.data?.type === "SKIP_WAITING") self.skipWaiting();
  if (event.data?.type === "WHAT_VERSION") {
    event.source &&
      event.source.postMessage({ type: "SW_VERSION", value: VERSION });
  }
});

async function staleWhileRevalidateSafe(cacheName, request) {
  // ★
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);

  try {
    const resp = await fetch(request);
    if (resp && (resp.ok || resp.type === "opaque")) {
      cache.put(request, resp.clone()).catch(() => {});
      return cached || resp;
    }
    return cached || resp || new Response("Offline", { status: 503 });
  } catch {
    return (
      cached ||
      new Response("Offline", { status: 503, statusText: "Network error" })
    );
  }
}

async function fontCacheFirst(request) {
  // ★
  const cache = await caches.open(CACHE_ASSETS);
  const hit = await cache.match(request);
  if (hit) return hit;

  try {
    const resp = await fetch(request);
    if (resp && (resp.ok || resp.type === "opaque")) {
      cache.put(request, resp.clone()).catch(() => {});
      return resp;
    }
    return new Response("", { status: 503 });
  } catch {
    return new Response("", { status: 503 });
  }
}

self.addEventListener("fetch", (event) => {
  const req = event.request;
  const url = new URL(req.url);

  const isNav = req.mode === "navigate" || req.destination === "document";
  if (isNav) {
    event.respondWith(networkFirstForPage(event, 8000));
    return;
  }

  if (req.method !== "GET") return;

  if (url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname.startsWith("/api/")) {
    if (API_EXCLUDES.some((p) => url.pathname.startsWith(p))) return;
    if (urlMatchesAny(url.pathname, API_GET_ALLOWLIST)) {
      event.respondWith(staleWhileRevalidateSafe(CACHE_API, req));
      return;
    }
    return;
  }

  if (isAsset(url)) {
    event.respondWith(staleWhileRevalidateSafe(CACHE_ASSETS, req));
    return;
  }

  if (isFont(url)) {
    event.respondWith(fontCacheFirst(req));
    return;
  }

  if (isImage(url)) {
    event.respondWith(cacheFirstImages(req));
    return;
  }
});
