import type { ReactNode } from 'react';

const Frame = ({ children, className = 'h-32 w-44' }: { children: ReactNode; className?: string }) => (
  <svg className={className} viewBox="0 0 176 128" fill="none" aria-hidden="true" focusable="false">
    <ellipse cx="88" cy="111" rx="66" ry="8" fill="#E0E7FF" />
    <path d="M32 84c-9-5-11-17-4-24 2-2 5-4 8-4 2-12 13-20 25-18 7-11 21-14 32-7 8-4 18-1 22 7 13-2 24 7 24 20 9 2 14 11 11 19-2 7-9 11-17 10H42c-4 0-7-1-10-3Z" fill="#EEF2FF" />
    {children}
  </svg>
);

export const NoDataIllustration = (props: { className?: string }) => (
  <Frame {...props}>
    <rect x="55" y="39" width="62" height="52" rx="7" fill="white" stroke="#818CF8" strokeWidth="2" />
    <path d="M67 54h37M67 64h26M67 74h31" stroke="#A5B4FC" strokeWidth="3" strokeLinecap="round" />
    <circle cx="116" cy="84" r="13" fill="#6366F1" />
    <path d="M112 84h8" stroke="white" strokeWidth="2.5" strokeLinecap="round" />
  </Frame>
);

export const NoResultsIllustration = (props: { className?: string }) => (
  <Frame {...props}>
    <rect x="48" y="43" width="67" height="45" rx="7" fill="white" stroke="#818CF8" strokeWidth="2" />
    <path d="M59 56h43M59 66h30M59 76h24" stroke="#C7D2FE" strokeWidth="3" strokeLinecap="round" />
    <circle cx="111" cy="81" r="14" fill="#6366F1" />
    <path d="m121 91 10 10" stroke="#4F46E5" strokeWidth="5" strokeLinecap="round" />
    <path d="m107 77 8 8m0-8-8 8" stroke="white" strokeWidth="2" strokeLinecap="round" />
  </Frame>
);

export const NoCitationsIllustration = (props: { className?: string }) => (
  <Frame {...props}>
    <path d="M55 42a7 7 0 0 1 7-7h44a7 7 0 0 1 7 7v40a7 7 0 0 1-7 7H62a7 7 0 0 1-7-7V42Z" fill="white" stroke="#818CF8" strokeWidth="2" />
    <path d="M67 52h34M67 63h27M67 74h19" stroke="#A5B4FC" strokeWidth="3" strokeLinecap="round" />
    <circle cx="114" cy="82" r="13" fill="#6366F1" />
    <path d="m108 82 4 4 8-9" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
  </Frame>
);

export const NoLicensesIllustration = (props: { className?: string }) => (
  <Frame {...props}>
    <rect x="50" y="42" width="68" height="48" rx="7" fill="white" stroke="#818CF8" strokeWidth="2" />
    <path d="M50 56h68M67 37v11m34-11v11" stroke="#6366F1" strokeWidth="3" strokeLinecap="round" />
    <path d="M65 67h8m8 0h8m8 0h8M65 78h8m8 0h8" stroke="#A5B4FC" strokeWidth="3" strokeLinecap="round" />
    <circle cx="114" cy="84" r="13" fill="#6366F1" />
    <path d="m108 84 4 4 8-9" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
  </Frame>
);

export const NotFoundIllustration = (props: { className?: string }) => (
  <Frame {...props}>
    <circle cx="87" cy="65" r="30" fill="white" stroke="#818CF8" strokeWidth="2" />
    <path d="M77 55a11 11 0 1 1 18 8c-5 4-8 5-8 11" stroke="#4F46E5" strokeWidth="4" strokeLinecap="round" />
    <circle cx="87" cy="82" r="2.5" fill="#4F46E5" />
    <path d="M52 98h72" stroke="#A5B4FC" strokeWidth="3" strokeLinecap="round" />
  </Frame>
);
