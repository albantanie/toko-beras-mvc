import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            {/* Rice grain icon */}
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M12 2C10.5 2 9.5 3 9.5 4.5C9.5 5.5 10 6.5 11 7.5C11.5 8 12 8.5 12 9.5C12 8.5 12.5 8 13 7.5C14 6.5 14.5 5.5 14.5 4.5C14.5 3 13.5 2 12 2ZM8 6C7 6 6 7 6 8.5C6 9.5 6.5 10.5 7.5 11.5C8 12 8.5 12.5 8.5 13.5C8.5 12.5 9 12 9.5 11.5C10.5 10.5 11 9.5 11 8.5C11 7 10 6 8 6ZM16 6C17 6 18 7 18 8.5C18 9.5 17.5 10.5 16.5 11.5C16 12 15.5 12.5 15.5 13.5C15.5 12.5 15 12 14.5 11.5C13.5 10.5 13 9.5 13 8.5C13 7 14 6 16 6ZM6 10C5 10 4 11 4 12.5C4 13.5 4.5 14.5 5.5 15.5C6 16 6.5 16.5 6.5 17.5C6.5 16.5 7 16 7.5 15.5C8.5 14.5 9 13.5 9 12.5C9 11 8 10 6 10ZM18 10C19 10 20 11 20 12.5C20 13.5 19.5 14.5 18.5 15.5C18 16 17.5 16.5 17.5 17.5C17.5 16.5 17 16 16.5 15.5C15.5 14.5 15 13.5 15 12.5C15 11 16 10 18 10ZM10 14C9 14 8 15 8 16.5C8 17.5 8.5 18.5 9.5 19.5C10 20 10.5 20.5 10.5 21.5C10.5 20.5 11 20 11.5 19.5C12.5 18.5 13 17.5 13 16.5C13 15 12 14 10 14ZM14 14C15 14 16 15 16 16.5C16 17.5 15.5 18.5 14.5 19.5C14 20 13.5 20.5 13.5 21.5C13.5 20.5 13 20 12.5 19.5C11.5 18.5 11 17.5 11 16.5C11 15 12 14 14 14Z"
                fill="currentColor"
            />
        </svg>
    );
}
