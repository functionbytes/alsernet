export function debounce<T extends (...args: unknown[]) => void>(fn: T, ms: number): T {
  let timer: ReturnType<typeof setTimeout>;
  return function (...args: unknown[]) {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  } as T;
}

export function throttle<T extends (...args: unknown[]) => void>(fn: T, ms: number): T {
  let lastRun = 0;
  return function (...args: unknown[]) {
    const now = Date.now();
    if (now - lastRun >= ms) {
      lastRun = now;
      fn(...args);
    }
  } as T;
}
