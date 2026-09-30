<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{ title: string; description: string; processing?: boolean }>();
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false"
                    >Annuleren</Button
                >
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="processing"
                    @click="emit('confirm')"
                    >Verwijderen</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
