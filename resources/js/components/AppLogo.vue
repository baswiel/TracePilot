<script setup lang="ts">
import TracePilotLogo from '@/components/TracePilotLogo.vue';
</script>

<template>
    <TracePilotLogo class="group-data-[collapsible=icon]:hidden dark:hidden" />
    <TracePilotLogo
        dark
        class="hidden group-data-[collapsible=icon]:hidden dark:block"
    />
    <TracePilotLogo
        compact
        class="hidden group-data-[collapsible=icon]:block"
    />
</template>
