import { ImgHTMLAttributes } from 'react';

export default function ApplicationLogo({ alt = 'AulaGen', ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return <img {...props} src="/favicon.svg" alt={alt} />;
}
