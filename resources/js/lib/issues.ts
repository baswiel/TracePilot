import type { SlaMilestone } from '@/types/issues';

export const slaTone = (state: SlaMilestone['state']) =>
    ({
        unavailable: 'border-border bg-muted text-muted-foreground',
        on_track: 'border-success/30 bg-success/10 text-success',
        at_risk: 'border-warning/30 bg-warning/10 text-warning',
        overdue: 'border-destructive/30 bg-destructive/10 text-destructive',
        met: 'border-success/30 bg-success/10 text-success',
        breached: 'border-destructive/30 bg-destructive/10 text-destructive',
    })[state];
