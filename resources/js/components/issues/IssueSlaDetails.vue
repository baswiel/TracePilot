<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { slaTone } from '@/lib/issues';
import { formatDate } from '@/lib/dates';
import type { IssueSla, SlaMilestone } from '@/types/issues';
defineProps<{ sla: IssueSla }>();
const deadlineLabel = (milestone: SlaMilestone) =>
    milestone.deadline_at
        ? formatDate(milestone.deadline_at)
        : 'Niet ingesteld';

const formatDuration = (minutes: number | null) => {
    if (minutes === null) return 'Niet beschikbaar';
    const absolute = Math.abs(minutes);
    const duration =
        absolute < 60
            ? `${absolute} min`
            : `${Math.floor(absolute / 60)} u ${absolute % 60} min`;

    return minutes < 0 ? `${duration} overschreden` : duration;
};
</script>
<template>
    <Card>
        <CardHeader>
            <CardTitle>SLA-bewaking</CardTitle>
            <CardDescription>
                Deadlines worden berekend vanaf het moment van melden op basis
                van de prioriteit.
            </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
            <section
                v-for="milestone in [sla.response, sla.resolution]"
                :key="milestone.label"
                class="rounded-lg border p-4"
                :class="slaTone(milestone.state)"
            >
                <p class="font-medium">{{ milestone.label }}</p>
                <p class="mt-1 text-sm">
                    Deadline: {{ deadlineLabel(milestone) }}
                </p>
                <p v-if="milestone.target_minutes" class="mt-1 text-sm">
                    Doel: {{ formatDuration(milestone.target_minutes) }}
                </p>
                <p
                    v-if="milestone.remaining_minutes !== null"
                    class="mt-1 text-sm font-medium"
                >
                    {{
                        milestone.remaining_minutes < 0
                            ? 'Overschrijding'
                            : 'Resterend'
                    }}: {{ formatDuration(milestone.remaining_minutes) }}
                </p>
            </section>
        </CardContent>
    </Card>
</template>
