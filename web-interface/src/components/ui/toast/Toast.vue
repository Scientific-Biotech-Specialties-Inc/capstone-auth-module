<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Primitive, type PrimitiveProps } from 'radix-vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const toastVariants = cva(
  'toast-item pointer-events-auto relative flex items-center gap-3.5 bg-white rounded-2xl p-3.5 pr-2.5 min-h-[58px] overflow-hidden transition-all cursor-default select-none border-0 outline-none',
  {
    variants: {
      variant: {
        default: '',
        success: '',
        error: '',
        info: '',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
)

type ToastVariants = VariantProps<typeof toastVariants>

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
  variant?: ToastVariants['variant']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
  variant: 'default',
})
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(toastVariants({ variant }), props.class)"
  >
    <slot />
  </Primitive>
</template>
