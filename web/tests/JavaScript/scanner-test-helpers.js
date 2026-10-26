export const element = () => {
    const attributes = new Map();

    return {
        classList: { add() {}, remove() {} },
        dataset: {},
        disabled: false,
        hidden: false,
        listeners: {},
        srcObject: null,
        textContent: '',
        addEventListener(type, handler) {
            this.listeners[type] = handler;
        },
        getAttribute(name) {
            return attributes.get(name) ?? null;
        },
        setAttribute(name, value) {
            attributes.set(name, value);
        },
    };
};

export const mediaStream = ({ applyConstraints, torch = false } = {}) => {
    const track = {
        stopped: false,
        applyConstraints: applyConstraints ?? (async () => {}),
        getCapabilities: () => ({ torch }),
        stop() {
            this.stopped = true;
        },
    };
    const value = {
        getTracks: () => [track],
        getVideoTracks: () => [track],
    };

    return { track, value };
};

export const settle = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
};
