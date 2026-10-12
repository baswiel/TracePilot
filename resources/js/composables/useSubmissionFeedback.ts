import type { VisitOptions } from '@inertiajs/core';
import { ref } from 'vue';

/** A failed response can arrive after the server has saved the change. */
export function useSubmissionFeedback(checkBeforeRetry: string) {
    const submissionError = ref('');
    const uncertainResult = `Opslaan kon niet worden bevestigd. ${checkBeforeRetry} Je invoer blijft bewaard.`;
    const failureCallbacks: Pick<
        VisitOptions,
        'onNetworkError' | 'onHttpException'
    > = {
        onNetworkError: () => {
            submissionError.value = `Controleer je verbinding. ${uncertainResult}`;
            return false;
        },
        onHttpException: (response) => {
            if (response.status === 403) {
                submissionError.value =
                    'Je hebt geen toestemming om deze wijziging op te slaan. Vraag je beheerder om toegang.';
            } else if (response.status === 429) {
                submissionError.value =
                    'Je hebt te vaak geprobeerd op te slaan. Wacht even en probeer opnieuw.';
            } else if (response.status >= 500) {
                submissionError.value = uncertainResult;
            } else {
                // Keep Inertia's session-expiry and other HTTP handling intact.
                return;
            }
            return false;
        },
    };
    return { submissionError, failureCallbacks };
}
