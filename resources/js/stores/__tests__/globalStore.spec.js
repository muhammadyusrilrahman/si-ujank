import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { useGlobalStore } from '../globalStore';

describe('global flash store', () => {
  const store = useGlobalStore();

  beforeEach(() => {
    store.initializeFlashes([]);
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('queues flashes and removes them after timeout', () => {
    store.addFlash({ type: 'success', message: 'Saved!', timeout: 500 });

    expect(store.state.flashes).toHaveLength(1);
    expect(store.state.flashes[0].message).toContain('Saved!');

    vi.advanceTimersByTime(500);

    expect(store.state.flashes).toHaveLength(0);
  });

  it('supports manual dismissal', () => {
    store.addFlash({ type: 'error', message: 'Something went wrong', timeout: 0 });
    expect(store.state.flashes).toHaveLength(1);

    const flashId = store.state.flashes[0].id;
    store.removeFlash(flashId);

    expect(store.state.flashes).toHaveLength(0);
  });
});
