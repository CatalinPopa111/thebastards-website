import { NoticeList } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

const Notices = () => {
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	return notices.length ? <NoticeList notices={ notices } /> : null;
};

export { Notices };
