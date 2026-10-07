<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="relative">
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('pr-10', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            :class="
                cn(
                    'text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 focus-visible:ring-[3px] focus-visible:outline-none',
                )
            "
            :aria-label="
                showPassword ? 'Wachtwoord verbergen' : 'Wachtwoord tonen'
            "
            :aria-pressed="showPassword"
        >
            <span
                class="t-icon-swap"
                :data-state="showPassword ? 'b' : 'a'"
                aria-hidden="true"
            >
                <Eye class="t-icon size-4" data-icon="a" />
                <EyeOff class="t-icon size-4" data-icon="b" />
            </span>
        </button>
    </div>
</template>
