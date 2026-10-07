import type { SlaMilestone } from '@/types/issues';

export const slaTone = (state: SlaMilestone['state']) =>
    ({
        unavailable: 'border-border bg-muted text-muted-foreground',
        on_track: 'tone-success',
        at_risk: 'tone-warning',
        overdue: 'tone-danger',
        met: 'tone-success',
        breached: 'tone-danger',
    })[state];
