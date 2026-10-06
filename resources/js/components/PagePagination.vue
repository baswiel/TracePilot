<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/pagination';

defineProps<{ page: Omit<Paginated<unknown>, 'data'> }>();
</script>

<template>
    <nav
        v-if="page.last_page > 1"
        aria-label="Paginering"
        class="flex flex-wrap items-center justify-between gap-3 p-4"
    >
        <p class="text-muted-foreground text-sm">
            Pagina {{ page.current_page }} van {{ page.last_page }} ·
            {{ page.total }} resultaten
        </p>
        <div class="flex gap-2">
            <Button v-if="page.prev_page_url" variant="outline" as-child
                ><Link :href="page.prev_page_url" preserve-scroll
                    >Vorige</Link
                ></Button
            >
            <Button v-if="page.next_page_url" variant="outline" as-child
                ><Link :href="page.next_page_url" preserve-scroll
                    >Volgende</Link
                ></Button
            >
        </div>
    </nav>
</template>
