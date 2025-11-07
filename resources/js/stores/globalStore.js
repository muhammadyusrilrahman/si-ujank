import { reactive } from 'vue';

const state = reactive({
  flashes: [],
});

let flashId = 0;
const timers = new Map();

const normalizeFlash = (flash) => ({
  id: ++flashId,
  type: flash.type ?? 'info',
  message: flash.message ?? '',
  timeout: Number.isFinite(flash.timeout) ? flash.timeout : 6000,
});

const clearTimer = (id) => {
  if (!timers.has(id)) {
    return;
  }
  clearTimeout(timers.get(id));
  timers.delete(id);
};

const removeFlash = (id) => {
  clearTimer(id);
  const index = state.flashes.findIndex((item) => item.id === id);
  if (index >= 0) {
    state.flashes.splice(index, 1);
  }
};

const scheduleRemoval = (flash) => {
  clearTimer(flash.id);
  if (!flash.timeout || flash.timeout <= 0) {
    return;
  }
  const timer = setTimeout(() => removeFlash(flash.id), flash.timeout);
  timers.set(flash.id, timer);
};

const addFlash = (flash) => {
  if (!flash || !flash.message) {
    return;
  }
  const normalized = normalizeFlash(flash);
  state.flashes.push(normalized);
  scheduleRemoval(normalized);
};

const initializeFlashes = (flashes = []) => {
  state.flashes.splice(0, state.flashes.length);
  timers.forEach((timer) => clearTimeout(timer));
  timers.clear();
  flashes.forEach((flash) => addFlash(flash));
};

export function useGlobalStore() {
  return {
    state,
    addFlash,
    removeFlash,
    initializeFlashes,
  };
}
