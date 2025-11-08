import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
        >
            <rect
                x="6"
                y="12"
                width="42"
                height="42"
                rx="12"
                fill="url(#timesheet-gradient)"
            />
            <path
                d="M22 22h14c3.314 0 6 2.686 6 6v8.5c0 6.351-5.149 11.5-11.5 11.5S19 42.851 19 36.5V28c0-3.314 2.686-6 6-6Z"
                fill="white"
                opacity="0.9"
            />
            <path
                d="M44 18c6.075 0 11 4.925 11 11v10c0 9.389-7.611 17-17 17H26c-6.075 0-11-4.925-11-11v-2h18c7.18 0 13-5.82 13-13V18Z"
                fill="white"
                opacity="0.55"
            />
            <circle cx="50" cy="18" r="8" fill="white" opacity="0.85" />
            <path
                d="M49 14v5.172a2 2 0 0 0 .586 1.414l2.828 2.828"
                stroke="url(#timesheet-stroke)"
                strokeWidth="2.4"
                strokeLinecap="round"
            />
            <defs>
                <linearGradient
                    id="timesheet-gradient"
                    x1="6"
                    y1="12"
                    x2="56"
                    y2="58"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#6366F1" />
                    <stop offset="0.5" stopColor="#22D3EE" />
                    <stop offset="1" stopColor="#34D399" />
                </linearGradient>
                <linearGradient
                    id="timesheet-stroke"
                    x1="49"
                    y1="14"
                    x2="55"
                    y2="21"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#1D4ED8" />
                    <stop offset="1" stopColor="#10B981" />
                </linearGradient>
            </defs>
        </svg>
    );
}
