<script setup lang="ts">
import QRCode from 'qrcode';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{ value: string; size?: number; color?: string }>();

const dataUrl = ref('');

const render = async () => {
    dataUrl.value = await QRCode.toDataURL(props.value, {
        width: props.size ?? 192,
        margin: 1,
        // Niveau M (~15 % de redondance) : suffisant pour une URL, QR plus léger qu'en Q/H.
        errorCorrectionLevel: 'M',
        color: { dark: props.color ?? '#000000', light: '#ffffff' },
    });
};

onMounted(render);
watch(() => [props.value, props.size, props.color], render);
</script>

<template>
    <img v-if="dataUrl" :src="dataUrl" :width="size ?? 192" :height="size ?? 192" :alt="__('QR code de l\'invitation')" />
</template>
