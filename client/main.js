import * as Sentry from "@sentry/browser";

// Wait half a second to allow the things to be loaded
setTimeout(() => {
    const sentryDSN = `${window.sentryendpoint}@${window.sentrydsn}`;

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

    if (window.version) {
        sentryConfig['release'] = window.version;
    }
    try {
        document.getElementById('sentryconf').remove();
        Sentry.init(sentryConfig);
    } catch (e) {
        //no-op
    }
}, 100);
