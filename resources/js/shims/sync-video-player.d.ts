declare module '@wooshiiltd/sync-video-player' {
    class SyncVideoPlayer {
        constructor(options: Record<string, unknown>);
        mount(): void;
        setVolume(volume: number): void;
        toggleMute(): void;
        timeTo(time: number): void;
        play(): Promise<void>;
        pause(): Promise<void>;

        on(event: string, callback: (...args: unknown[]) => void): void;
        on(event: 'timeUpdate', callback: (time: number) => void): void;
        on(event: 'stateChange', callback: (state: string) => void): void;
    }
    export default SyncVideoPlayer;
}
