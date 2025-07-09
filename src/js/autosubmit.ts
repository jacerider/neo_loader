(function (Drupal, once) {

  function watchFormElementAndSubmit(
    element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement,
    submit: HTMLFormElement,
    debounceMs: number = 300
  ): void {

    if (!element) {
      console.error(`Element not found`);
      return;
    }

    if (!isSubmitElement(submit)) {
      console.error(`Submit element is not a submit button:`, element);
      return;
    }
    submit.classList.add('sr-only');
    const actions = submit.closest('.form-actions');
    if (actions instanceof HTMLElement) {
      if (!getVisibleChildren(actions).length) {
        actions.classList.add('sr-only');
      }
    }

    let timeoutId: number;
    let previousValue: string | boolean = element.type === 'checkbox' || element.type === 'radio'
    ? (element as HTMLInputElement).checked
    : element.value;

    // Function to handle value change
    const handleValueChange = (): void => {
      const currentValue = element.type === 'checkbox' || element.type === 'radio'
      ? (element as HTMLInputElement).checked
      : element.value;

      // Only submit if value actually changed
      if (currentValue !== previousValue) {
        previousValue = currentValue;

        // Clear any existing timeout
        if (timeoutId) {
          clearTimeout(timeoutId);
        }

        // Debounce the form submission
        timeoutId = window.setTimeout(() => {
          Drupal.behaviors.neoLoader.show('Loading...');
          submit.click();
          const mousedownEvent = new MouseEvent('mousedown', {
            bubbles: true,
            cancelable: true,
            button: 0, // Simulate left-click
            clientX: 200,
            clientY: 150
          });
          submit.dispatchEvent(mousedownEvent);
        }, debounceMs);
      }
    };

    // Add event listeners for different input types
    if (element.type === 'checkbox' || element.type === 'radio') {
      // For checkboxes and radio buttons, 'change' is the primary event
      element.addEventListener('change', handleValueChange);
    } else if (element.tagName === 'TEXTAREA' ||
      (element.tagName === 'INPUT' && (element.type === 'text' || element.type === 'email' || element.type === 'password' || element.type === 'search' || element.type === 'url' || element.type === 'tel'))) {
        // For text inputs and textareas, use blur to avoid submitting on every keystroke
        element.addEventListener('blur', handleValueChange);
      } else {
        // For other elements (select, etc.), use change and blur
        element.addEventListener('change', handleValueChange);
        element.addEventListener('blur', handleValueChange);
      }
    }

    function isSubmitElement(element: HTMLElement): boolean {
      return (element.tagName === 'BUTTON' && (element as HTMLButtonElement).type === 'submit') ||
      (element.tagName === 'INPUT' && (element as HTMLInputElement).type === 'submit');
    }

    function getVisibleChildren(parentElement: HTMLElement): HTMLElement[] {
      const visibleChildren: HTMLElement[] = [];

      if (parentElement) {
        const childElements = parentElement.querySelectorAll('a, button, input');

        for (let i = 0; i < childElements.length; i++) {
          const child = childElements[i] as HTMLElement;
          const computedStyle = window.getComputedStyle(child);

          if (computedStyle.display !== 'none' &&
            computedStyle.visibility !== 'hidden' &&
            child.classList.contains('sr-only') === false &&
            child.classList.contains('hidden') === false &&
            parseFloat(computedStyle.opacity) > 0) {
              visibleChildren.push(child);
            }
          }
        }
        return visibleChildren;
      }

      Drupal.behaviors.neoAutosubmit = {

        attach: function (context:any) {
          once('neo-loader', '.use-neo-autosubmit', context).forEach((element) => {
            if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)) {
              console.warn('Element is not an input, select, or textarea:', element);
              return;
            }
            const form = element.closest('form');
            if (form) {
              const actions = form.querySelectorAll<HTMLElement>('.form-actions');
              if (actions.length > 0) {
                const lastAction = actions[actions.length - 1];
                const submit: HTMLFormElement | null = lastAction.querySelector('.form-submit');
                if (submit) {
                  watchFormElementAndSubmit(element, submit);
                }
              }
            }
          });
        }

      };

    })(Drupal, once);

    export {};
