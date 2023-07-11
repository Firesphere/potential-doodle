import * as Sentry from "@sentry/browser";

// Wait half a second to allow the things to be loaded
const sentryDSN = window.sentrydsn;

const sentryConfig = {
    dsn: sentryDSN,
    tracesSampleRate: 0.8,
    replaysSessionSampleRate: 0.1,

    // If the entire session is not sampled, use the below sample rate to sample
    // sessions when an error occurs.
    replaysOnErrorSampleRate: 1.0,

    integrations: [
        new Sentry.BrowserTracing(),
        new Sentry.Replay({
            // Additional SDK configuration goes in here, for example:
            maskAllText: true,
            blockAllMedia: true,
        }),
    ],
};

export default () => {
    if (window.version) {
        sentryConfig['release'] = window.version;
    }
    try {
        Sentry.init(sentryConfig);
        document.getElementById('sentryconf').remove();
    } catch (e) {
        //no-op
    }
};
