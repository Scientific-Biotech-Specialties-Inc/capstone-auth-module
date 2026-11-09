import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ConfirmModal from '../ConfirmModal.vue'

describe('ConfirmModal component', () => {
  it('does not render dialog content when show is false', () => {
    const wrapper = mount(ConfirmModal, {
      props: {
        show: false,
      },
      attachTo: document.body,
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('renders title and description when show is true', () => {
    mount(ConfirmModal, {
      props: {
        show: true,
        title: 'Custom Title',
        description: 'Custom Description Message',
      },
      attachTo: document.body,
    })

    expect(document.body.textContent).toContain('Custom Title')
    expect(document.body.textContent).toContain('Custom Description Message')
  })

  it('emits confirm when confirm button is clicked', async () => {
    const wrapper = mount(ConfirmModal, {
      props: {
        show: true,
        confirmLabel: 'Yes Proceed',
      },
      attachTo: document.body,
    })

    const buttons = document.body.querySelectorAll('button')
    const confirmBtn = Array.from(buttons).find((b) => b.textContent?.includes('Yes Proceed'))
    expect(confirmBtn).toBeTruthy()

    confirmBtn?.click()
    expect(wrapper.emitted('confirm')).toBeTruthy()
  })

  it('emits close when cancel button is clicked', async () => {
    const wrapper = mount(ConfirmModal, {
      props: {
        show: true,
        cancelLabel: 'Cancel Action',
      },
      attachTo: document.body,
    })

    const buttons = document.body.querySelectorAll('button')
    const cancelBtn = Array.from(buttons).find((b) => b.textContent?.includes('Cancel Action'))
    expect(cancelBtn).toBeTruthy()

    cancelBtn?.click()
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
