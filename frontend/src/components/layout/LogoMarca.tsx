import { IMAGES } from '@/config/images';

export const LogoMarca = ({ compact = false, className = '', loading = 'lazy' }: { compact?: boolean; className?: string; loading?: 'lazy' | 'eager' }) => (
  <span className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-1 shadow-sm ${className}`}>
    <img
      src={IMAGES.logo}
      alt="Logo del Sistema Escolar"
      width={compact ? 36 : 44}
      height={compact ? 36 : 44}
      loading={loading}
      className={compact ? 'size-9 scale-150 object-cover' : 'size-11 object-contain'}
    />
  </span>
);
