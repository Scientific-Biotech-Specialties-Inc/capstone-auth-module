<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Primitive, type PrimitiveProps } from 'radix-vue'
import { cn } from '@/lib/utils'

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
})
</script>

<template>
  <Teleport to="body">
    <Primitive
      :as="as"
      :as-child="asChild"
      :class="cn('fixed bottom-8 right-8 z-[9999] flex flex-col gap-3 pointer-events-none w-full max-w-sm sm:max-w-md px-4 sm:px-0 outline-none border-0', props.class)"
    >
      <slot />
    </Primitive>
  </Teleport>
</template>
