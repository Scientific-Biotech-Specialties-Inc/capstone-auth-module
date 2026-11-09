<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { ToastDescription, type ToastDescriptionProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<ToastDescriptionProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props
  return delegated
})
</script>

<template>
  <ToastDescription
    v-bind="delegatedProps"
    :class="cn('text-xs text-slate-500 font-normal', props.class)"
  >
    <slot />
  </ToastDescription>
</template>
